<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZKTecoImporter;
use App\Jobs\AttendanceRollupJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class AttendanceImportController extends Controller
{
    public function import(Request $request)
    {
        $importer = app(ZKTecoImporter::class);

        $usersPath = null;
        $logsPath  = null;

        // 1) Try uploaded files first (as before)
        if ($request->hasFile('users') && $request->hasFile('logs')) {
            $request->validate([
                'users' => ['file', 'mimetypes:application/json,text/plain'],
                'logs'  => ['file', 'mimetypes:application/json,text/plain'],
            ]);

            $usersPath = $request->file('users')->store('imports'); // storage/app/imports/...
            $logsPath  = $request->file('logs')->store('imports');
            [$uc, $lc] = $importer->importFromStorage($usersPath, $logsPath);
        } else {
            // 2) Fallback: base directory JSONs
            $usersFull = base_path('users_data.json');
            $logsFull  = base_path('attendance_data.json');

            if (!File::exists($usersFull) || !File::exists($logsFull)) {
                return response()->json([
                    'message' => 'users_data.json এবং attendence_data.json প্রজেক্ট বেস ডিরেক্টরিতে পাওয়া যায়নি।',
                    'hint' => 'ফাইল দুটা রাখুন: ' . $usersFull . ' এবং ' . $logsFull . ' অথবা form-data দিয়ে users/logs আপলোড করুন।'
                ], 422);
            }

            // Service-এ absolute path দিয়ে সরাসরি ইমপোর্ট
            [$uc, $lc] = $importer->importUsersJson($usersFull);
            // উপরের লাইন users count রিটার্ন করে; logs-ও আলাদা চালাই:
            $lc = $importer->importLogsJson($logsFull);
        }

        // Rollup last 7 days
        $from = Carbon::now('Asia/Dhaka')->subDays(20)->startOfDay()->toDateTimeString();
        $to   = Carbon::now('Asia/Dhaka')->endOfDay()->toDateTimeString();
        AttendanceRollupJob::dispatch($from, $to);

        return response()->json([
            'message'     => 'Import successful',
            'users_count' => $uc ?? 0,
            'logs_count'  => $lc ?? 0,
            'rollup'      => ['from' => $from, 'to' => $to]
        ]);
    }

    public function importFromBase(ZKTecoImporter $importer)
    {
        $usersFull = base_path('users_data.json');
        $logsFull  = base_path('attendance_data.json');

        if (!file_exists($usersFull) || !file_exists($logsFull)) {
            return response()->json([
                'message' => 'Base ডিরেক্টরিতে users_data.json / attendance_data.json পাওয়া যায়নি।',
                'paths'   => compact('usersFull', 'logsFull')
            ], 422);
        }

        $uc = $importer->importUsersJson($usersFull);
        $lc = $importer->importLogsJson($logsFull);

        $from = Carbon::now('Asia/Dhaka')->subDays(7)->startOfDay()->toDateTimeString();
        $to   = Carbon::now('Asia/Dhaka')->endOfDay()->toDateTimeString();
        AttendanceRollupJob::dispatch($from, $to);

        return response()->json([
            'message'     => 'Base import successful',
            'users_count' => $uc,
            'logs_count'  => $lc,
            'rollup'      => ['from' => $from, 'to' => $to],
            'files'       => ['users' => $usersFull, 'logs' => $logsFull]
        ]);
    }

    public function peekBase()
    {
        $logsFull = base_path('attendance_data.json'); // আপনার path
        if (!file_exists($logsFull)) {
            return response()->json(['message' => 'attendance_data.json not found', 'path' => $logsFull], 422);
        }

        $raw = file_get_contents($logsFull);
        $head = substr($raw, 0, 500);

        // খুব সাধারণ ফরম্যাট ডিটেকশন
        $looksJson = preg_match('/^\s*[\{\[]/m', $head) === 1;
        $looksCsv  = str_contains($head, ',') && preg_match('/User|Check|Time|Log|PIN|Badge|Emp/i', $head);

        return response()->json([
            'size_bytes' => strlen($raw),
            'head' => $head,              // প্রথম 500 বাইট
            'detected' => [
                'json' => $looksJson ? true : false,
                'csv'  => $looksCsv ? true : false,
            ],
        ]);
    }
}
