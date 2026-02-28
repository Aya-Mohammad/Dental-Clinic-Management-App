<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\PatientData;
use App\Models\Doctor;
use App\Models\Secretary;
use App\Models\SearchHistory;
use App\Models\Clinic;
use App\Models\Treatment;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Role;
use App\Models\Notification;
use App\Traits\RoleTrait;
use Illuminate\Support\Facades\DB;

/**
 * @property \Illuminate\Database\Eloquent\Collection|Role[] $roles
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany roles()
 */

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable , RoleTrait;

    protected $fillable = [
        'name',
        'email',
        'number',
        'password',
        'profile_image',
        'otp',
        'expire_at',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at' => 'datetime',
        'expire_at' => 'datetime',
    ];

    public function patientData()
    {
        return $this->hasOne(PatientData::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function secretary()
    {
        return $this->hasOne(Secretary::class);
    }

    public function searchHistories()
    {
        return $this->hasMany(SearchHistory::class);
    }

    public function clinics()
    {
        return $this->hasMany(Clinic::class, 'user_id');
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favourites()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_user', 'user_id', 'clinic_id')
            ->with(['street.city'])
            ->select('clinics.*', DB::raw('1 as is_favourite'));
    }

    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

}
