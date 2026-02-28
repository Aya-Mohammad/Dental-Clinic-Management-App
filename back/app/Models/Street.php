<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use App\Models\PatientData;
use App\Models\Clinic;

class Street extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city_id',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function patientData()
    {
        return $this->hasMany(PatientData::class);
    }

    public function clinics()
    {
        return $this->hasMany(Clinic::class);
    }
}
