<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\treatment;

class ToothTreatment extends Model
{
    use HasFactory;

    protected $table = 'tooth_treatment';
    protected $fillable = [
        'tooth_number',
        'treatment_id',
    ];

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

}
