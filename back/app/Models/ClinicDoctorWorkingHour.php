<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClinicDoctor;
use App\Models\WorkingHour;

class ClinicDoctorWorkingHour extends Model
{
    use HasFactory;

    protected $table = 'clinic_doctor_working_hour';
    protected $fillable = [
        'clinic_doctor_id',
        'working_hour_id',
        'working_day'
    ];

    public function clinic_doctor()
    {
        return $this->belongsTo(ClinicDoctor::class);
    }

    public function working_hour()
    {
        return $this->belongsTo(WorkingHour::class);
    }
}
