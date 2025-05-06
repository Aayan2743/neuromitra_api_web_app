<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class assesementguideModel extends Model
{
    use HasFactory;

    public $table="assesementguide";

    public $timestamps=false;

    public $guarded=[];
}
