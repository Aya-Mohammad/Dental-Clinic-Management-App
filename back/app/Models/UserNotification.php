<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;

class UserNotification extends Model
{
   use HasFactory;

    protected $table = 'user_notifications';

   protected $casts = [ 'file' => 'array', 'is_read' => 'boolean', ];

    protected $fillable = [
        'type',
        'title',
        'messages',
        'is_read',
        'data',
        'user_id'
    ];
   public function user() { return $this->belongsTo(User::class); }

}

