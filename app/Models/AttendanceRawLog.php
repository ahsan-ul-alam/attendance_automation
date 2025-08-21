<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRawLog extends Model {
    protected $fillable = ['employee_code','punch_time','device','payload'];
    protected $casts = [
        'punch_time'=>'datetime',
        'payload'=>'array'
    ];
}
