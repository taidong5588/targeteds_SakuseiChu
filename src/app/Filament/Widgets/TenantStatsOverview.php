<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tenant;
use App\Models\AdminUser;
use Carbon\Carbon;

class TenantStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '60s';
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';

    /**
     * System Admin only
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof AdminUser
            && $user->isSystemAdmin();
    }

    protected function getStats(): array
    {
        $now = Carbon::now();

        // Active tenants with valid contracts
        $activeTenants = Tenant::with(['tenantPlan.plan'])
            ->where('is_active', true)
            ->whereHas('tenantPlan', function ($q) use ($now) {
                $q->where('contract_start_at', '<=', $now)
                  ->where(function ($sq) use ($now) {
                      $sq->whereNull('contract_end_at')
                         ->orWhere('contract_end_at', '>=', $now);
                  });
            })
            ->get();

        // Monthly Recurring Revenue (MRR)
        $totalMrr = $activeTenants->sum(fn ($t) =>
            $t->tenantPlan->contract_price_override
            ?? $t->tenantPlan->plan?->base_price
            ?? 0
        );

        // Contracts expiring this month
        $expiringCount = Tenant::where('is_active', true)
            ->whereHas('tenantPlan', function ($q) use ($now) {
                $q->whereBetween('contract_end_at', [
                    $now->startOfDay(),
                    $now->copy()->endOfMonth(),
                ]);
            })
            ->count();

        // Overall counts
        $totalTenants    = Tenant::count();
        $inactiveTenants = Tenant::where('is_active', false)->count();

        return [
            Stat::make('Total MRR (Monthly Revenue)', '$' . number_format($totalMrr))
                ->description('Sum of active contracts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Active Contracts', $activeTenants->count())
                ->description('Currently under contract')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make('Contracts Expiring This Month', $expiringCount)
                ->description($expiringCount > 0 ? 'Action required' : 'No upcoming expirations')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($expiringCount > 0 ? 'danger' : 'gray'),

            Stat::make('Total Tenants', $totalTenants)
                ->description('All registered tenants')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Inactive Tenants', $inactiveTenants)
                ->description('Expired or disabled')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
