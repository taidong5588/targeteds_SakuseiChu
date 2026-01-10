<?php

namespace App\Services;

use App\Models\TenantPlan;

class BillingCalculator
{
    public static function calculate(TenantPlan $tenantPlan, int $usage = 0): array
    {
        $plan = $tenantPlan->plan;

        // --- 1. 基本料金の算出優先順位 ---
        // A. 個別上書き価格 (contract_price_override)
        // B. プラン標準月額 (base_price)
        // C. 年額の月換算 (annual_fee / 12) ※base_priceが0の場合などのフォールバック
        
        if ($tenantPlan->contract_price_override !== null) {
            $base = $tenantPlan->contract_price_override;
        } elseif ($plan->base_price > 0) {
            $base = $plan->base_price;
        } else {
            $base = $plan->annual_fee / 12;
        }

        // --- 2. 従量料金（超過分）の計算 ---
        // pricing_typeが 'unlimited' の場合は超過を計算しない
        $usageCharge = 0;
        if ($plan->pricing_type !== 'unlimited') {
            $overageCount = max(0, $usage - $plan->included_mails);
            $usageCharge = $overageCount * $plan->overage_unit_price;
        }

        // --- 3. 小計と割引適用 ---
        $subtotal = $base + $usageCharge;

        if ($tenantPlan->discount_type === 'rate') {
            $subtotal *= (1 - ($tenantPlan->discount_value / 100));
        } elseif ($tenantPlan->discount_type === 'fixed') {
            $subtotal -= $tenantPlan->discount_value;
        }

        $subtotal = max(0, $subtotal);

        // --- 4. 税計算 ---
        $tax = $subtotal * ($plan->tax_rate / 100);

        return [
            'plan_name'     => $plan->name,
            'base_amount'   => round($base),
            'usage_actual'  => $usage,
            'overage_count' => $plan->pricing_type === 'unlimited' ? 0 : max(0, $usage - $plan->included_mails),
            'usage_charge'  => round($usageCharge),
            'subtotal'      => round($subtotal),
            'tax'           => round($tax),
            'total'         => round($subtotal + $tax),
        ];
    }
}