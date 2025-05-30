<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject

{
    use HasApiTokens, HasFactory, Notifiable;


    public function getJWTIdentifier(){
        return $this->getKey();
     }
     
     public function getJWTCustomClaims(){
        return [];
     }
     
    
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected $appends = ['profile_pic_url'];

    public function getProfilePicUrlAttribute(): ?string
    {
        if (! $this->profile_pic) {
            return null;
        }

        // Use Storage::url() if you prefer “/storage/…”;
        // asset() will prepend full scheme+host.
        return asset('storage/' . $this->profile_pic);
    }

 
}
