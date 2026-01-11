<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Models\Tenant;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        // 💡 効率的に要対応テナントを抽出
        $criticalCount = Tenant::all()
            ->filter(fn ($t) => $t->contractState() === 'trial_critical')
            ->count();

        $actions = [];

        // 💡 件数がある時だけ赤い警告ボタンを表示
        if ($criticalCount > 0) {
            $actions[] = Action::make('attention')
                ->label("🚨 Trial 要対応: {$criticalCount} 件")
                ->color('danger')
                ->extraAttributes(['class' => 'animate-bounce font-bold'])
                ->disabled();
        }

        $actions[] = CreateAction::make();

        return $actions;
    }
}