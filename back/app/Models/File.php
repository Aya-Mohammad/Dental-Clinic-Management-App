<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Treatment;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'treatment_id',
    ];

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}
