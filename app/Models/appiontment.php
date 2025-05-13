<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class appiontment extends Model
{
    use HasFactory;

    public $table="appiontments";

    public $timestamps=false;
    public $guarded=[];

    protected $casts = [
        'calender_days' => 'array',
    ];

    public function assignedSlots()
{
    // if your pivot/model is `AssignedAppointment` with table `assigned_appointments`
    return $this->hasMany(AssignedAppointment::class, 'app_id');
}

       public function staff()
{

    return $this->hasOne(user::class, 'staff_id');
}

 public function user()
    {
        // user_id is the FK on the appointments table
        return $this->belongsTo(User::class, 'user_id');
    }

}
