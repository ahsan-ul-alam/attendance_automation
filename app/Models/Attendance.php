<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model {
    protected $fillable = ['employee_id','date','in_time','out_time','work_minutes','status','locked'];
    protected $casts = [
        'date'=>'date',
        'in_time'=>'datetime',
        'out_time'=>'datetime',
        'locked'=>'boolean'
    ];
    public function employee(){ return $this->belongsTo(Employee::class); }
}
