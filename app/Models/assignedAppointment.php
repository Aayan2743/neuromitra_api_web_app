<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\appiontment;

class assignedAppointment extends Model
{
    use HasFactory;

    public $table="assigned";

    public $timestamps=false;

    public $guarded=[];

    protected $casts = [
        'calender_days' => 'array',    // JSON ↔ array
        'starting_date' => 'date',
        'ending_date'   => 'date',
        'starting_time' => 'string',
        'ending_time'   => 'string',
    ];

    public function appointment()
    {
        return $this->belongsTo(appiontment::class, 'app_id', 'id');
    }
}
