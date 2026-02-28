<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Service;
use App\Models\Clinic;
use App\Models\ToothTreatment;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\File;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_number',
        'clinic_id',
        'user_id',
        'services',
        'status',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_treatment')
                    ->withPivot('order', 'stage_number')
                    ->withTimestamps();
    }

    public function toothTreatments()
    {
        return $this->hasMany(ToothTreatment::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class)
        ->orderBy('date');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

}
