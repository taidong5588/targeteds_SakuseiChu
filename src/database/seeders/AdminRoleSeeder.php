<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminRole; // 🚀 これが必要です！

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        // スーパー管理者
        AdminRole::updateOrCreate(['code' => 'super_admin'], [
            'name' => 'Super Admin',
            'description' => 'Full access to all tenants and system settings.'
        ]);

        // テナント管理者
        AdminRole::updateOrCreate(['code' => 'tenant_admin'], [
            'name' => 'Tenant Admin',
            'description' => 'Full access to the assigned tenant data.'
        ]);

        // 閲覧者
        AdminRole::updateOrCreate(['code' => 'viewer'], [
            'name' => 'Viewer',
            'description' => 'Read-only access to the assigned tenant data.'
        ]);
    }
}