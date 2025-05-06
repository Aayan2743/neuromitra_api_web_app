<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class assementguideanswer extends Model
{
    use HasFactory;

    public $table="assement_guide_answers";

    public $timestamps=false;

    public $guarded=[];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
