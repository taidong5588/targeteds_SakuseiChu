<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'plan',
        'status',
        'default_locale',
        'mail_enabled',
    ];

    // 必要に応じてリレーションなどを追加
    // 例: public function users() { return $this->hasMany(User::class); }
}
