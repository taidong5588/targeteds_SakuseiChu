<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use App\Notifications\AdminResetPasswordNotification;

class AdminUser extends Authenticatable implements CanResetPassword
{
    use Notifiable, CanResetPasswordTrait;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',    // super_admin
        'locale',  // 言語切替用
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 監査ログなど必要に応じ追加
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'actor');
    }

    // パスワードリセット通知
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminResetPasswordNotification($token));
    }
}
