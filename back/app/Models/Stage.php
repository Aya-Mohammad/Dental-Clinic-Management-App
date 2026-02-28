<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ServiceStage;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'duration',
        'title',
        'description',
        'specialization'
    ];

    public const SPECIALIZATIONS = [
        'C' => 'Cardiology',
        'G' => 'General',
        'E' => 'Endodontics',
        'O' => 'Orthodontics',
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class)
            ->using(ServiceStage::class)
            ->withPivot('order')
            ->withTimestamps();
    }

    public static function getSpecializations(): array
    {
        return self::SPECIALIZATIONS;
    }
}
