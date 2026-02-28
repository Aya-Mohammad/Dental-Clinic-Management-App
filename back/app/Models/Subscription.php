<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscribable_id',
        'subscribable_type',
        'subscribed_at',
        'duration_days',
        'amount',
        'status', 
    ];

    public function subscribable()
    {
        return $this->morphTo();
    }

}
