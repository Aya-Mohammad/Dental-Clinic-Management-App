<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicSecretary extends Model
{
    use HasFactory;
    protected $table = 'clinic_secretary';
    
    protected $fillable = [
        'clinic_id',
        'secretary_id',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    public function secretary()
    {
        return $this->belongsTo(Secretary::class, 'secretary_id');
    }

    public function workingHours()
    {
        return $this->belongsToMany(WorkingHour::class, 'clinic_secretary_working_hour')
            ->withPivot('working_day')
            ->withTimestamps();
    }

}
