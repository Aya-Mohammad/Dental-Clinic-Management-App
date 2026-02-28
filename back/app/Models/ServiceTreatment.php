<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTreatment extends Model
{
    use HasFactory;

    protected $table = 'service_treatment';
    protected $fillable = [
        'order',
        'stage_number',
        'treatment_id',
        'service_id',
    ];

    public function treatment(){
        return $this->belongsTo(Treatment::class);
    }

    public function service(){
        return $this->belongsTo(Service::class);
    }
}
