<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class child extends Model
{
    use HasFactory;

    public $table="childs";

    // public $timestamps=false;

    public $guarded=[];
}
