<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicSecretaryWorkingHour extends Model
{
    use HasFactory;

    protected $table = 'clinic_secretary_working_hour';
    protected $fillable = [
        'clinic_secretary_id',
        'working_hour_id',
        'working_day'
    ];

    public function clinic_secretary()
    {
        return $this->belongsTo(ClinicSecretary::class);
    }

    public function working_hour()
    {
        return $this->belongsTo(WorkingHour::class);
    }
}
