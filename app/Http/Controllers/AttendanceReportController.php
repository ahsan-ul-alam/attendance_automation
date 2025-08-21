<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceReportController extends Controller
{
    // JSON API
    public function daily(Request $req)
    {
        $date = $req->query('date', Carbon::now('Asia/Dhaka')->toDateString());
        $rows = Attendance::with('employee')
            ->whereDate('date', $date)
            ->orderBy('employee_id')
            ->get()
            ->map(function ($a) {
                return [
                    'employee' => $a->employee->name,
                    'code' => $a->employee->employee_code,
                    'date' => $a->date->toDateString(),
                    'in' => optional($a->in_time)?->format('H:i'),
                    'out' => optional($a->out_time)?->format('H:i'),
                    'work_hours' => round($a->work_minutes / 60, 2),
                    'status' => $a->status,
                ];
            });

        return response()->json($rows);
    }

    // Blade page
    public function dailyPage(Request $req)
    {
        $date = $req->query('date', Carbon::now('Asia/Dhaka')->toDateString());
        $rows = Attendance::with('employee')
            ->whereDate('date', $date)
            ->orderBy('employee_id')
            ->get();

        return view('attendance.daily', compact('rows', 'date'));
    }
}
