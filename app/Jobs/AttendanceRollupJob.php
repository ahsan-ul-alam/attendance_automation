<?php

namespace App\Jobs;

use App\Models\{AttendanceRawLog, Attendance, Employee, Holiday, Shift, LeaveModel};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceRollupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $from;
    public string $to;

    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function handle(): void
    {
        $from = Carbon::parse($this->from, 'Asia/Dhaka')->startOfDay();
        $to   = Carbon::parse($this->to, 'Asia/Dhaka')->endOfDay();

        $period = CarbonPeriod::create($from->toDateString(), $to->toDateString());
        $employees = Employee::all()->keyBy('employee_code');

        foreach ($period as $day) {
            $date = $day->toDateString();

            $isHoliday = Holiday::whereDate('date', $date)->exists();

            $logs = AttendanceRawLog::query()
                ->whereDate('punch_time', $date)
                ->orderBy('punch_time')
                ->get()
                ->groupBy('employee_code');

            foreach ($employees as $code => $emp) {
                $empLogs = $logs->get($code, collect());
                $in  = $empLogs->first()->punch_time ?? null;
                $out = $empLogs->count() > 1 ? $empLogs->last()->punch_time : $in;

                $shift = $emp->shifts()->orderByDesc('employee_shift.effective_from')->first()
                    ?: new Shift(['start'=>'09:00:00','end'=>'18:00:00','grace_minutes'=>10,'lunch_minutes'=>60]);

                $status = 'Absent';
                $workMin = 0;

                if ($isHoliday) {
                    $status = 'Holiday';
                } else {
                    $onLeave = LeaveModel::where('employee_id', $emp->id)
                        ->where('status','Approved')
                        ->whereDate('start_date','<=',$date)
                        ->whereDate('end_date','>=',$date)
                        ->exists();

                    if ($onLeave) {
                        $status = 'Leave';
                    } elseif ($in) {
                        $workMin = $in && $out ? Carbon::parse($in)->diffInMinutes(Carbon::parse($out)) : 0;
                        if ($workMin >= 5*60) $workMin -= ($shift->lunch_minutes ?? 60);

                        $start = Carbon::parse("$date {$shift->start}", 'Asia/Dhaka');
                        $grace = (clone $start)->addMinutes($shift->grace_minutes ?? 10);
                        $noon  = Carbon::parse("$date 12:00:00",'Asia/Dhaka');

                        if (Carbon::parse($in)->gt($noon) || $workMin < 240) {
                            $status = 'Half';
                        } elseif (Carbon::parse($in)->gt($grace)) {
                            $status = 'Late';
                        } else {
                            $status = 'Present';
                        }
                    }
                }

                Attendance::updateOrCreate(
                    ['employee_id'=>$emp->id, 'date'=>$date],
                    [
                        'in_time' => $in,
                        'out_time'=> $out,
                        'work_minutes'=> max(0,$workMin),
                        'status'=> $status
                    ]
                );
            }
        }
    }
}
