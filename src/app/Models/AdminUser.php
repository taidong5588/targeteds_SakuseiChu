<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable implements CanResetPassword
{
    use Notifiable, CanResetPasswordTrait;

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

}
