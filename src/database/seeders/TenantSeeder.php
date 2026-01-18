<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Language;
use Illuminate\Support\Carbon;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * 1. 必要なマスタを取得
         * （DatabaseSeeder で作成されている前提）
         */
        $plan = Plan::where('code', 'bundle_1000')->first()
            ?? Plan::where('code', 'internal')->first(); // フォールバック

        $lang = Language::where('code', 'ja')->first();

        // 万が一マスタが存在しない場合は明示的に停止
        if (!$plan || !$lang) {
            throw new \RuntimeException(
                'Master data (Plan or Language) not found. Please run DatabaseSeeder first.'
            );
        }

        /**
         * 2. 内部管理用テナント
         */
        Tenant::updateOrCreate(
            ['code' => 'internal'],
            [
                'name'                     => 'Internal Admin',
                'notify_name'              => '管理者',
                'notify_email'             => 'admin@example.local',
                'plan_id'                  => $plan->id,
                'language_id'              => $lang->id,
                'is_active'                => true,
                'audit_log_retention_days' => 9999,
                'trial_start_at'           => null,
                'trial_ends_at'            => null,
            ]
        );

        /**
         * 3. デモ用テナント（トライアルあり）
         */
        Tenant::updateOrCreate(
            ['code' => 'demo-corp'],
            [
                'name'                     => 'デモ株式会社',
                'notify_name'              => '山田 太郎',
                'notify_email'             => 'demo@example.local',
                'plan_id'                  => $plan->id,
                'language_id'              => $lang->id,
                'is_active'                => true,
                'audit_log_retention_days' => 90,
                'trial_start_at'           => Carbon::now(),
                'trial_ends_at'            => Carbon::now()->addDays(14),
            ]
        );
    }
}
