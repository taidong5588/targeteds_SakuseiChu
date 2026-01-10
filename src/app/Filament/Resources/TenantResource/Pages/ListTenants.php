<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Models\Tenant;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        $count = Tenant::query()
            ->where('is_active', true) // 現在有効なものだけ
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '>=', now()->startOfDay())
            ->whereDate('trial_ends_at', '<=', now()->addDays(3)->endOfDay())
            ->count();

        return array_merge(
            $count > 0 ? [
                Action::make('attentionTenants')
                    ->label("🚨 要対応テナント {$count} 件")
                    ->color('danger')
                    ->extraAttributes(['class' => 'animate-bounce font-bold'])
                    ->url('#'), // 必要ならフィルタ済みのURLへ
            ] : [],
            parent::getHeaderActions()
        );
    }
}