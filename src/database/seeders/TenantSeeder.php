<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Language;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 必要なマスタを取得（DatabaseSeeder で作られている前提）
        $plan = Plan::where('code', 'bundle_1000')->first() 
                ?? Plan::where('code', 'internal')->first(); // 念のためのフォールバック

        $lang = Language::where('code', 'ja')->first();

        // ⚠️ 万が一マスタがない場合のためのガード
        if (!$plan || !$lang) {
            throw new \Exception('Master data (Plan or Language) not found. Please run DatabaseSeeder first.');
        }

        // 2. テナントを作成（updateOrCreate で重複回避）
        Tenant::updateOrCreate(
            ['code' => 'internal'], // codeを検索キーにする
            [
                'name' => 'Internal Admin',
                'plan_id' => $plan->id,
                'language_id' => $lang->id,
                'is_active' => true,
                'audit_log_retention_days' => 9999,
                'trial_ends_at' => null,
            ]
        );

        // デモ用テナント
        Tenant::updateOrCreate(
            ['code' => 'demo-corp'],
            [
                'name' => 'デモ株式会社',
                'plan_id' => $plan->id,
                'language_id' => $lang->id,
                'is_active' => true,
                'audit_log_retention_days' => 90,
                'trial_ends_at' => now()->addDays(14), // 14日間のトライアル
            ]
        );
    }
}