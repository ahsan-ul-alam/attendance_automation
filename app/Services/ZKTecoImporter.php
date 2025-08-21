<?php

namespace App\Services;

use App\Models\{Employee, AttendanceRawLog};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ZKTecoImporter
{
    /** usersPath, logsPath: storage paths (e.g. imports/xyz.json) */
    public function importFromStorage(string $usersPath, string $logsPath): array
    {
        $usersCount = $this->importUsersJson(Storage::path($usersPath));
        $logsCount  = $this->importLogsJson(Storage::path($logsPath));
        return [$usersCount, $logsCount];
    }

    public function importUsersJson(string $fullPath): int
    {
        $json = json_decode(file_get_contents($fullPath), true);
        $rows = collect(data_get($json, 'data', []))->values();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $code = (string)($row['userid'] ?? $row['employee_id'] ?? null);
                if (!$code) continue;
                Employee::updateOrCreate(
                    ['employee_code' => $code],
                    ['name' => $row['name'] ?? 'Unknown']
                );
            }
        });

        return $rows->count();
    }

    public function importLogsJson(string $fullPath): int
    {
        $raw = @file_get_contents($fullPath);
        if ($raw === false || trim($raw) === '') return 0;

        $json = json_decode($raw, true);
        if (!is_array($json)) return 0;

        // --- Try daily summaries shape: { status, data: [ {employee_id, date, in_time, out_time, ...} ] }
        $maybeDaily = data_get($json, 'data');
        if (is_array($maybeDaily) && !empty($maybeDaily)) {
            $first = $maybeDaily[0] ?? [];
            if (isset($first['employee_id']) && isset($first['date']) && isset($first['in_time'])) {
                return $this->importDailySummaries($maybeDaily);
            }
        }

        // --- Fallback to previous generic handlers (logs/records/attendance etc) ---
        $candidates = [
            $json,
            data_get($json, 'data'),
            data_get($json, 'records'),
            data_get($json, 'logs'),
            data_get($json, 'attendance'),
            data_get($json, 'attendances'),
            data_get($json, 'result'),
        ];
        foreach ($candidates as $cand) {
            if (is_array($cand)) {
                if (array_keys($cand) !== range(0, count($cand) - 1)) $cand = array_values($cand);
                // যদি cand-এও in/out থাকে, তবুও daily হিসেবে নিন
                $first = $cand[0] ?? [];
                if (isset($first['employee_id']) && isset($first['date']) && isset($first['in_time'])) {
                    return $this->importDailySummaries($cand);
                }
                // অন্যথায় raw-log হ্যান্ডলার (যদি দরকার হয়)
                return $this->insertAttendanceRows($cand);
            }
        }

        return 0;
    }

    /**
     * Daily summary → attendances upsert
     */
    private function importDailySummaries(array $rows): int
    {
        $tz = 'Asia/Dhaka';

        // map employee_code -> id
        $employees = \App\Models\Employee::query()
            ->select('id', 'employee_code')
            ->get()
            ->keyBy('employee_code');

        $count = 0;

        foreach ($rows as $r) {
            if (!is_array($r)) continue;

            $code = (string)($r['employee_id'] ?? '');
            $date = $r['date'] ?? null;
            $in   = $r['in_time'] ?? null;
            $out  = $r['out_time'] ?? null;

            if ($code === '' || !$date) continue;

            $emp = $employees->get($code);
            if (!$emp) continue; // unknown employee code

            // Parse times (nullable)
            $inAt  = $in  ? \Carbon\Carbon::parse($in,  $tz) : null;
            $outAt = $out ? \Carbon\Carbon::parse($out, $tz) : $inAt;

            // Shift defaults (same as earlier rules)
            $shift = \App\Models\Shift::query()->first()
                ?: new \App\Models\Shift(['start' => '09:00:00', 'end' => '18:00:00', 'grace_minutes' => 10, 'lunch_minutes' => 60]);

            // Work minutes
            $workMin = 0;
            if ($inAt && $outAt) {
                $workMin = $inAt->diffInMinutes($outAt);
                if ($workMin >= 5 * 60) $workMin -= ($shift->lunch_minutes ?? 60);
            }

            // Status rules
            $status = 'Absent';
            $isHoliday = \App\Models\Holiday::whereDate('date', $date)->exists();
            if ($isHoliday) {
                $status = 'Holiday';
            } else {
                $onLeave = \App\Models\LeaveModel::where('employee_id', $emp->id)
                    ->where('status', 'Approved')
                    ->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date)
                    ->exists();
                if ($onLeave) {
                    $status = 'Leave';
                } elseif ($inAt) {
                    $start = \Carbon\Carbon::parse("$date {$shift->start}", $tz);
                    $grace = (clone $start)->addMinutes($shift->grace_minutes ?? 10);
                    $noon  = \Carbon\Carbon::parse("$date 12:00:00", $tz);

                    if ($inAt->gt($noon) || $workMin < 240) {
                        $status = 'Half';
                    } elseif ($inAt->gt($grace)) {
                        $status = 'Late';
                    } else {
                        $status = 'Present';
                    }
                }
            }

            \App\Models\Attendance::updateOrCreate(
                ['employee_id' => $emp->id, 'date' => $date],
                [
                    'in_time'      => $inAt,
                    'out_time'     => $outAt,
                    'work_minutes' => max(0, $workMin),
                    'status'       => $status,
                ]
            );

            $count++;
        }

        return $count;
    }
}
