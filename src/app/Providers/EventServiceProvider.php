<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\AdminLoginAuditListener;
use App\Listeners\AdminLogoutAuditListener;

/**
 * ============================================================
 * EventServiceProvider
 * ------------------------------------------------------------
 * Laravel のイベントとリスナーを紐付ける場所
 * - 認証イベント（login / logout）
 * - 管理者操作の監査用途
 * ============================================================
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * イベントとリスナーの対応表
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        /**
         * 管理者ログイン監査
         */
        Login::class => [
            AdminLoginAuditListener::class,
        ],

        /**
         * 管理者ログアウト監査
         */
        Logout::class => [
            AdminLogoutAuditListener::class,
        ],
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
