<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\{Advertisment};

class AdvertismentImages extends Model
{
    use HasFactory;

    protected $table = 'advertisment_images';
    protected $fillable = [
        'path',
        'advertisment_id',
    ];

    public function advertisment()
    {
        return $this->belongsTo(Advertisment::class);
    }
}
