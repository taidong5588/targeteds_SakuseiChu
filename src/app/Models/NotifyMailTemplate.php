<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifyMailTemplate extends Model
{
    protected $fillable = [
        'title',
        'key',
        'slug',
        'channel',
        'subject',
        'body',
        'allowed_variables',
        'description',
        'is_active',
    ];

    protected $casts = [
        'allowed_variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
