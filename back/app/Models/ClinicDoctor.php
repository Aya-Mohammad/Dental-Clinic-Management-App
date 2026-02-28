<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\ClinicDoctorWorkingHour;

class ClinicDoctor extends Model
{
    use HasFactory;

    protected $table = 'clinic_doctor';
    
    protected $fillable = [
        'clinic_id',
        'doctor_id',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function workingHours()
    {
        return $this->belongsToMany(WorkingHour::class, 'clinic_doctor_working_hour')
            ->withPivot('working_day')
            ->withTimestamps();
    }

}
