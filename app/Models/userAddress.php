<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userAddress extends Model
{
    use HasFactory;

      public $table="user_address";

    public $timestamps=false;
    public $guarded=[];
}
