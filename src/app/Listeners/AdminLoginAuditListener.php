<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Auth\Events\Login;
use App\Models\AdminAuditLog;

/**
 * ============================================================
 * 管理者ログイン監査リスナー
 * ------------------------------------------------------------
 * - admin ガードのログインのみ記録
 * - 成功ログインを確実に監査ログへ保存
 * ============================================================
 */
class AdminLoginAuditListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // admin ガード以外（user 等）は無視
        if ($event->guard !== 'admin') {
            return;
        }

        $admin = $event->user;

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'tenant_id'     => $admin->tenant_id ?? null,
            'action'        => 'login',
            'target_type'   => get_class($admin),
            'target_id'     => $admin->id,
            'before'        => null,
            'after'         => [
                'email' => $admin->email,
            ],
            'ip'            => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'occurred_at'   => now(),
        ]);
    }
}
