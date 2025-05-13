<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class session_tracking_details extends Model
{
    use HasFactory;
     public $table="session_tracking";

    public $timestamps=false;

    public $guarded=[];

}
