<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Language;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\AdminRole;
use pp\Models\NotifyMailTemplate;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
public function run(): void
    {
        // 1. Language
        $ja = Language::updateOrCreate(
            ['code' => 'ja'],
            ['name' => '日本語', 'is_active' => true]
        );

        Language::updateOrCreate(['code' => 'en'], ['name' => 'English', 'is_active' => true]);
        Language::updateOrCreate(['code' => 'ko'], ['name' => '한국어', 'is_active' => true]);
        Language::updateOrCreate(['code' => 'zh_CN'], ['name' => '简体中文', 'is_active' => true]);

        // 2. 依存順を保証
        $this->call([
            PlanSeeder::class,
            AdminRoleSeeder::class,
            TenantSeeder::class,
            AdminUserSeeder::class,
            NotifyMailTemplateSeeder::class,
        ]);
    }

}