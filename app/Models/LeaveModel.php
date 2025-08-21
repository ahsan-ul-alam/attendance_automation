<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveModel extends Model {
    protected $table = 'leaves';
    protected $fillable = ['employee_id','start_date','end_date','type','status'];
    protected $casts = ['start_date'=>'date','end_date'=>'date'];
}
