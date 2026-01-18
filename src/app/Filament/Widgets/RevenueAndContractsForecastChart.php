<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Tenant;
use App\Models\AdminUser;
use Carbon\Carbon;

// class RevenueAndContractsForecastChart extends ChartWidget
// {
//     protected static ?string $heading = 'Revenue & Contracts Forecast';
//     protected static ?int $sort = 2;
//     protected static ?string $pollingInterval = '5min';
//     protected static bool $isLazy = true;
//     protected int|string|array $columnSpan = 'full';

//     /**
//      * ✅ システム管理者のみ表示
//      */
//     public static function canView(): bool
//     {
//         $user = auth()->user();

//         return $user instanceof AdminUser
//             && $user->isSystemAdmin();
//     }

//     protected function getData(): array
//     {
//         $labels = [];
//         $revenueData = [];
//         $contractCountData = [];

//         // 🔹 今月〜6ヶ月先
//         for ($i = 0; $i < 6; $i++) {
//             $month = Carbon::now()->addMonths($i);
//             $monthStart = $month->copy()->startOfMonth();
//             $monthEnd   = $month->copy()->endOfMonth();

//             $labels[] = $month->format('Y/m');

//             // 🔍 その月に有効な契約を持つテナント
//             $tenants = Tenant::with(['tenantPlan.plan'])
//                 ->where('is_active', true)
//                 ->whereHas('tenantPlan', function ($q) use ($monthStart, $monthEnd) {
//                     $q->where('contract_start_at', '<=', $monthEnd)
//                       ->where(function ($q2) use ($monthStart) {
//                           $q2->whereNull('contract_end_at')
//                              ->orWhere('contract_end_at', '>=', $monthStart);
//                       });
//                 })
//                 ->get();

//             // 💰 売上合計
//             $revenueData[] = $tenants->sum(fn ($t) =>
//                 $t->tenantPlan->contract_price_override
//                 ?? $t->tenantPlan->plan?->base_price
//                 ?? 0
//             );

//             // 🏢 契約社数
//             $contractCountData[] = $tenants->count();
//         }

//         return [
//             'labels' => $labels,
//             'datasets' => [
//                 [
//                     'label' => __('Revenue (JPY)'),
//                     'data' => $revenueData,
//                     'type' => 'line',
//                     'borderColor' => '#3b82f6',
//                     'backgroundColor' => '#3b82f6',
//                     'borderWidth' => 3,
//                     'tension' => 0.4,
//                     'yAxisID' => 'y',
//                 ],
//                 [
//                     'label' => __('Active Contracts'),
//                     'data' => $contractCountData,
//                     'type' => 'bar',
//                     'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
//                     'borderColor' => '#3b82f6',
//                     'borderWidth' => 1,
//                     'yAxisID' => 'y1',
//                 ],
//             ],
//         ];
//     }

//     protected function getOptions(): array
//     {
//         return [
//             'responsive' => true,
//             'scales' => [
//                 'y' => [
//                     'position' => 'left',
//                     'title' => [
//                         'display' => true,
//                         'text' => __('Revenue (JPY)'),
//                     ],
//                 ],
//                 'y1' => [
//                     'position' => 'right',
//                     'grid' => [
//                         'drawOnChartArea' => false,
//                     ],
//                     'ticks' => [
//                         'precision' => 0,
//                     ],
//                     'title' => [
//                         'display' => true,
//                         'text' => __('Contracts'),
//                     ],
//                 ],
//             ],
//         ];
//     }

//     protected function getType(): string
//     {
//         // datasets 側で line / bar を切り替える
//         return 'line';
//     }
// }
class RevenueAndContractsForecastChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Forecast';
    protected static ?int $sort = 2;
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';

    /**
     * システム管理者のみ表示
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof AdminUser
            && $user->isSystemAdmin();
    }

    /**
     * グラフデータ生成（計算の心臓部）
     */
    protected function getData(): array
    {
        // ==========================
        // 1. Page から渡される条件
        // ==========================
        $startDate = $this->startDate
            ? Carbon::parse($this->startDate)->startOfMonth()
            : now()->startOfMonth();

        $endDate = $this->endDate
            ? Carbon::parse($this->endDate)->endOfMonth()
            : now()->addMonths(5)->endOfMonth();

        $tenantId = $this->tenantId ?? null;

        // ==========================
        // 2. ラベル & データ配列
        // ==========================
        $labels = [];
        $revenueData = [];

        // ==========================
        // 3. 対象契約の取得
        // ==========================
        $query = TenantPlan::with(['plan']);

        // 特定テナント指定時のみ絞り込み
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $tenantPlans = $query->get();

        // ==========================
        // 4. 月単位で売上を計算
        // ==========================
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd   = $current->copy()->endOfMonth();

            $labels[] = $current->format('Y/m');

            $monthlyRevenue = $tenantPlans
                ->filter(function ($contract) use ($monthStart, $monthEnd) {
                    // 契約がその月に有効か判定
                    return $contract->contract_start_at <= $monthEnd
                        && (
                            $contract->contract_end_at === null
                            || $contract->contract_end_at >= $monthStart
                        );
                })
                ->sum(function ($contract) {
                    // ==========================
                    // 優しい金額計算ロジック
                    // override → plan price → 0
                    // ==========================
                    return (float) (
                        $contract->contract_price_override
                        ?? $contract->plan?->base_price
                        ?? 0
                    );
                });

            $revenueData[] = $monthlyRevenue;

            $current->addMonth();
        }

        // ==========================
        // 5. Chart.js 用データ返却
        // ==========================
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Monthly Revenue',
                    'data' => $revenueData,
                    'type' => 'line',
                    'borderWidth' => 3,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * グラフタイプ
     */
    protected function getType(): string
    {
        return 'line';
    }
}