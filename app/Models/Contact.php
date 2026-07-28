<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'comment',
        'sentiment',
        'type',
        'auto_reply',
        'ai_used',
    ];

    protected function casts(): array
    {
        return [
            'ai_used' => 'boolean',
        ];
    }
}
