<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';
    protected $fillable=[
        'type',
        'title',
        'messages',
        'is_read',
        'data',
        'user_id'
    ];
}
