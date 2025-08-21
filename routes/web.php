<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceReportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hrms/attendance/daily', [AttendanceReportController::class, 'dailyPage']);
