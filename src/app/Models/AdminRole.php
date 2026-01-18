<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    protected $table = 'admin_roles';

    // 一括保存を許可するカラムを指定します
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'admin_role_id');
    }

}