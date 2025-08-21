<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model {
    protected $fillable = ['name','start','end','grace_minutes','lunch_minutes'];
}
