<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class health_daily_reports extends Model
{
    use HasFactory;
    
    public $table="health_daily_report";
   

   public $guarded = [];


    public $timestamps = false;
    
     public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    
}
