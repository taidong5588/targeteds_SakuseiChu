<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Tenant;
use App\Models\AdminUser;
// 必要に応じて他の監査対象モデルをuse
// use App\Models\Campaign;
// use App\Models\MailTemplate;
use App\Observers\AdminAuditObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 管理画面で操作されるモデルを監査対象にする
        Tenant::observe(AdminAuditObserver::class);
        AdminUser::observe(AdminAuditObserver::class); // AdminUser自身の変更も監査対象に

        // 今後監査対象にしたいモデルはここにどんどん追加していきます
        // 例:
        // Campaign::observe(AdminAuditObserver::class);
        // MailTemplate::observe(AdminAuditObserver::class);
        // User::observe(AdminAuditObserver::class); // テナントに紐づくユーザーも監査対象に
    }
}
