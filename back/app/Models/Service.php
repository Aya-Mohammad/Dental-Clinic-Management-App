<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Treatment;
use App\Models\ServiceStage;
use App\Models\ClinicService;

class Service extends Model
{
    use HasFactory;

    const specialization_needed = ['C', 'G', 'E', 'O'];
    protected $fillable = [
        'name',
        'description',
        'duration',
        'stages_number',
    ];

    public function treatments()
    {
        return $this->belongsToMany(Treatment::class, 'service_treatment')
                    ->withPivot('order', 'stage_number')
                    ->withTimestamps();
    }

    public function stages()
    {
        return $this->belongsToMany(Stage::class)
            ->using(ServiceStage::class)
            ->withPivot('order')
            ->withTimestamps();
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class)
            ->using(ClinicService::class)
            ->withPivot('price')
            ->withTimestamps();
    }
}
