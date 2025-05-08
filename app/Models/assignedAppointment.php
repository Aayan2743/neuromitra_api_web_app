<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class assignedAppointment extends Model
{
    use HasFactory;

    public $table="assigned";

    public $timestamps=false;

    public $guarded=[];
}
