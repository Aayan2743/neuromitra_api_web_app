<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class testcase extends Model
{
    use HasFactory;

      public $table="assigned_test";

    public $timestamps=false;

    public $guarded=[];
}
