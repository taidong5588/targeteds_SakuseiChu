<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {

        Plan::updateOrCreate(
            ['code' => 'bundle_1000'],
            [
                'name' => 'ベーシック1000（パック型）',
                'pricing_type' => 'bundle',
                'base_price' => 25000.00,        // 月額換算 2.5万円
                'annual_fee' => 300000,         // 年換算 30万円
                'included_mails' => 1000,       // 1000通まで無料
                'overage_unit_price' => 400,    // 超過1通 400円
                'default_retention_days' => 365,
                'tax_rate' => 10.00,
            ]
        );

        Plan::updateOrCreate(
            ['code' => 'pay_as_you_go'],
            [
                'name' => '従量課金プラン（低基本料）',
                'pricing_type' => 'metered',
                'base_price' => 5000.00,         // 基本料は安い
                'included_mails' => 0,          // 無料枠なし
                'overage_unit_price' => 100,     // 1通目から100円
                'default_retention_days' => 90,
                'tax_rate' => 10.00,
            ]
        );

        Plan::updateOrCreate(
            ['code' => 'enterprise_fixed'],
            [
                'name' => 'エンタープライズ（定額無制限）',
                'pricing_type' => 'unlimited',
                'base_price' => 100000.00,       // 高めの固定額
                'included_mails' => 999999,      // 事実上無制限
                'overage_unit_price' => 0,
                'default_retention_days' => 1000,
                'tax_rate' => 10.00,
                'calculation_rule' => ['special_discount' => 'vip_client'], // 特殊ルール用
            ]
        );

        Plan::updateOrCreate(
            ['code' => 'internal'],
            [
                'name' => 'システム管理プラン',
                'pricing_type' => 'unlimited',
                'base_price' => 0.00,
                'annual_fee' => 0,
                'default_retention_days' => 9999,
                'tax_rate' => 10.00,
            ]
        );

    }
}