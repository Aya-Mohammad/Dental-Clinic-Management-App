<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic_Working_Hour extends Model
{
    use HasFactory;

    protected $table = 'clinic_working_hour';

    protected $fillable = [
        'clinic_id', 'working_hour_id', 'working_day'
    ];
}
