<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // 一括保存を許可するカラムを指定します
    protected $fillable = [
        'name',
        'code',
        'description',
    ];
}