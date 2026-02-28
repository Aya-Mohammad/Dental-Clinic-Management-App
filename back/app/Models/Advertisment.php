<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\{AdvertismentImages, Clinic, Subscription};

class Advertisment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'clinic_id',
        'subscribed_at',
        'id',
        'subscription_duration_days',
        'status',
    ];

    protected $casts = [
    'subscribed_at' => 'datetime',
    ];

    public function images()
    {
        return $this->hasMany(AdvertismentImages::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

}
