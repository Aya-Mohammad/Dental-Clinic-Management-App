<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ServiceStage extends Pivot
{
    use HasFactory;

    protected $table = 'service_stage';

    protected $fillable = [
        'service_id',
        'stage_id',
        'order',
    ];
}
