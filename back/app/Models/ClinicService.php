<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Models\Clinic;
use App\Models\Service;

class ClinicService extends Pivot
{
    protected $table = 'clinic_service';
    protected $fillable = [
        'clinic_id',
        'service_id',
        'price',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

