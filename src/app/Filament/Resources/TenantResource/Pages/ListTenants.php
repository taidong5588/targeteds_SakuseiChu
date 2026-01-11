<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use App\Filament\Resources\TenantResource\Widgets\TenantOverview;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    /**
     * 💡 統計を開いているかどうかのフラグ
     */
    public bool $isStatsOpen = false;

    protected function getHeaderActions(): array
    {
        $stats = Tenant::getStateStats();
        $criticalCount = $stats['trial_critical'] ?? 0;

        return [
            Action::make('toggleStats')
                ->label(fn() => $this->isStatsOpen 
                    ? "統計を閉じる" 
                    : ($criticalCount > 0 ? "🚨 要対応 {$criticalCount} 件" : "📊 統計表示")
                )
                ->icon(fn() => $this->isStatsOpen ? 'heroicon-m-x-mark' : 'heroicon-m-chart-bar')
                ->color(fn() => ($criticalCount > 0 && !$this->isStatsOpen) ? 'danger' : 'gray')
                ->extraAttributes(fn() => ($criticalCount > 0 && !$this->isStatsOpen) ? ['class' => 'animate-bounce'] : [])
                ->action(function () {
                    // 1. 自身のボタン表示を切り替え
                    $this->isStatsOpen = !$this->isStatsOpen;
                    // 2. ウィジェットに「表示しろ/隠せ」とイベントを飛ばす
                    $this->dispatch('toggleTenantStats');
                }),

            CreateAction::make()->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TenantOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}