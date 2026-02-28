<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\{Treatment, Appointment, Doctor};

class AppointmentTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_id',
        'title',
        'description',
        'appointment_id',
        'doctor_id',
    ];

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
