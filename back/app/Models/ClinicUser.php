<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Clinic;
use App\Models\User;

class ClinicUser extends Model
{
    use HasFactory;

    protected $table = 'clinic_user';
    protected $fillable = [
        'clinic_id',
        'user_id',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
