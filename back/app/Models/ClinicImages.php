<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Clinic;

class ClinicImages extends Model
{
    use HasFactory;

    protected $table = 'clinic_images';
    protected $fillable = [
        'path',
        'clinic_id',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}

