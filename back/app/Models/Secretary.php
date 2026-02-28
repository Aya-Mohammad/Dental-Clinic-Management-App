<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Clinic;
use App\Models\ClinicSecretary;

class Secretary extends Model
{
    use HasFactory;

    protected $fillable = [
        'bio',
        'user_id',
        'clinic_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_secretary', 'secretary_id', 'clinic_id');
    }

    public function clinicSecretary()
    {
        return $this->hasMany(ClinicSecretary::class);
    }
}
