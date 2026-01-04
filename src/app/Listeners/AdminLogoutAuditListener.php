<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Auth\Events\Logout;
use App\Models\AdminAuditLog;

/**
 * ============================================================
 * 管理者ログアウト監査リスナー
 * ------------------------------------------------------------
 * - admin ガードのログアウトのみ記録
 * - セッション破棄前に証跡を残す
 * ============================================================
 */
class AdminLogoutAuditListener
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
        if ($event->guard !== 'admin' || !$event->user) {
            return;
        }

        $admin = $event->user;

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'tenant_id'     => $admin->tenant_id ?? null,
            'action'        => 'logout',
            'target_type'   => get_class($admin),
            'target_id'     => $admin->id,
            'before'        => null,
            'after'         => null,
            'ip'            => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'occurred_at'   => now(),
        ]);
    }
}
