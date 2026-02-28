<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClinicDoctorWorkingHour;
use App\Models\SecretaryWorkingHour;

class WorkingHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'start',
        'end',
    ];

    public function clinic_doctors()
    {
        return $this->belongsToMany(ClinicDoctor::class)
            ->using(ClinicDoctorWorkingHour::class)
            ->withPivot('working_days')
            ->withTimestamps();
    }

    public function secretaries()
    {
        return $this->belongsToMany(Secretary::class)
            ->using(SecretaryWorkingHour::class)
            ->withPivot('working_day')
            ->withTimestamps();
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_working_hour')
            ->withPivot('working_day')
            ->withTimestamps();
    }
}
