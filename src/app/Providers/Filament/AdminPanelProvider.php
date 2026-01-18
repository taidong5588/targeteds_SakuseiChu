<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\SetFilamentLocale; // 追加
use Illuminate\Support\HtmlString;
use App\Filament\Widgets\RevenueAndContractsForecastChart; // ✅ これを追加
use App\Filament\Widgets\TenantStatsOverview;              // ✅ これも追加

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->authGuard('admin')
            ->bootUsing(function () {
                // config([
                //     'auth.defaults.guard' => 'admin',
                //     'auth.defaults.passwords' => 'admin_users',
                // ]); 
                config()->set('auth.defaults.passwords', 'admin_users');
            })
            ->bootUsing(function () {
                //
            })
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                RevenueAndContractsForecastChart::class, // ✅ 追加
                TenantStatsOverview::class,              // ✅ 追加
                
                // // 1段目：最重要KPI (Stat)
                // \App\Filament\Widgets\RevenueKpiOverview::class, // MRR, ARR, NRR
                // \App\Filament\Widgets\MrrGrowthStat::class,      // 成長率
                // \App\Filament\Widgets\TrialConversionStat::class, // コンバージョン

                // // 2段目：分析グラフ & コホート
                // \App\Filament\Widgets\RevenueAndContractsForecastChart::class,
                // \App\Filament\Widgets\CohortNrrTable::class,

                // // 3段目：アクションが必要なリスト
                // \App\Filament\Widgets\ExpiringTenantsTable::class, // 今月終了
                // \App\Filament\Widgets\AtRiskTenantsTable::class,   // 低稼働
                // \App\Filament\Widgets\ChurnPredictionTable::class, // 解約スコア

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetFilamentLocale::class, // 追加
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                        'panels::body.end',
                        fn () => new \Illuminate\Support\HtmlString("
                            <script>
                                window.addEventListener('insert-variable', (e) => {
                                    // FilamentのRichEditor(Trix)を探す
                                    const container = document.querySelector('.template-body-editor');
                                    if (!container) return;
                                    
                                    const trixEditor = container.querySelector('trix-editor');
                                    if (trixEditor && trixEditor.editor) {
                                        trixEditor.focus();
                                        trixEditor.editor.insertString(e.detail.text);
                                    }
                                });
                            </script>
                        ")
                    );

                }
}
