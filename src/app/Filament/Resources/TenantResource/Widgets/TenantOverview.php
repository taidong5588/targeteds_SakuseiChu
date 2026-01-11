<?php

namespace App\Filament\Resources\TenantResource\Widgets;

use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class TenantOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = false;

    // 🔑 表示状態の管理（初期は非表示）
    public bool $visible = false;
    protected int|string|array $columnSpan = 'full';

    /**
     * 🔔 親（ListTenants）からのイベントを受信
     */
    #[On('toggleTenantStats')]
    public function toggle(): void
    {
        $this->visible = !$this->visible;
    }

    /**
     * ❗ 非表示のときはHTMLを生成しない
     */
    public function shouldRender(): bool
    {
        return $this->visible;
    }

    protected function getStats(): array
    {
        // 念のため非表示時は計算もしない
        if (!$this->visible) return [];

        $stats = Tenant::getStateStats();

        return [
            Stat::make('稼働中 (Active)', $stats['active'])
                ->description('本契約・正常なテナント')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('trial 要対応 (≤3日)', $stats['trial_critical'])
                ->description('3日以内に終了するトライアル')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->extraAttributes([
                    'class' => $stats['trial_critical'] > 0
                        ? 'animate-pulse font-bold'
                        : '',
                ]),

            Stat::make('開始前 (Upcoming)', $stats['upcoming'])
                ->description('契約開始日待ち')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('期限切れ (Expired)', $stats['expired'])
                ->description('自動停止中')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('gray'),

            Stat::make(
                '月間予測収益',
                '¥' . number_format($stats['total_revenue'])
            )
                ->description('稼働中テナントの基本料金合計')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}