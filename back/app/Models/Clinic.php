<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\{Review, Appointment, Secretary, ClinicImages,
    Advertisment, ClinicService, ClinicDoctor, Subscription, User, Treatment};

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'bio',
        'street_id',
        'user_id',
        'subscribed_at',
        'subscription_duration_days'
    ];

    protected $casts = [
    'subscribed_at' => 'datetime',
    ];

    public function street()
    {
        return $this->belongsTo(Street::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advertisments()
    {
        return $this->hasMany(Advertisment::class);
    }

    public function clinicImages()
    {
        return $this->hasMany(ClinicImages::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function secretaries()
    {
        return $this->belongsToMany(Secretary::class, 'clinic_secretary', 'clinic_id', 'secretary_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'clinic_service')
                    ->withPivot(['price', 'accessibility'])
                    ->withTimestamps();
    }
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'clinic_doctor', 'clinic_id', 'doctor_id');
    }

    public function clinicDoctors()
    {
        return $this->hasMany(ClinicDoctor::class);
    }

    public function workingHours()
    {
        return $this->belongsToMany(WorkingHour::class, 'clinic_working_hour')
            ->withPivot('working_day')
            ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

}
