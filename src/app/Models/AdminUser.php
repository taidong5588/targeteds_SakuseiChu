<?php

namespace App\Models;

use App\Traits\AuditsRoles; // 👈 追加
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Notifications\Notifiable;
use App\Models\AdminRole;

class AdminUser extends Authenticatable implements CanResetPassword
{
    use Notifiable, CanResetPasswordTrait;
    use AuditsRoles; // 👈 追加（bootAuditsRoles を動く）

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'admin_role_id',   // 追加
        'language_id', // 追加
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];   
    
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function adminRole()
    {
        return $this->belongsTo(AdminRole::class);
    }

    public function language() 
    { 
        return $this->belongsTo(Language::class); 
    }

    public function isSystemAdmin(): bool
    {
        return $this->adminRole?->code === 'system_admin';
    }

}
