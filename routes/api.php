<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceImportController;
use App\Http\Controllers\AttendanceReportController;

Route::post('/hrms/attendance/import', [AttendanceImportController::class, 'import']);
Route::get('/hrms/attendance/daily',  [AttendanceReportController::class, 'daily']);
Route::post('/hrms/attendance/base-import', [AttendanceImportController::class, 'importFromBase']);
Route::post('/hrms/attendance/peek-base', [AttendanceImportController::class, 'peekBase']);

