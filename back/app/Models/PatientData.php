<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Street;
use App\Models\MedicalRecord;

class PatientData extends Model
{
    use HasFactory;

    protected $fillable = [
        'gender',
        'date_of_birth',
        'blood_type',
        'street_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function street()
    {
        return $this->belongsTo(Street::class);
    }

}
