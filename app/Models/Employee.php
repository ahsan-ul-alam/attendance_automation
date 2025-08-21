<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model {
    protected $fillable = ['employee_code','name','email'];

    public function attendances(){ return $this->hasMany(Attendance::class); }
    public function shifts(){ return $this->belongsToMany(Shift::class)->withTimestamps()->withPivot('effective_from'); }
    public function leaves(){ return $this->hasMany(LeaveModel::class,'employee_id'); }
}
