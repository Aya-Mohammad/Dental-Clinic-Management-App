<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SearchHistory extends Model
{
    use HasFactory;

    protected $table = 'search_history';
    protected $fillable = [
        'search_term',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
