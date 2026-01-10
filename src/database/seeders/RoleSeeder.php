<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role; // 🚀 これが必要です！

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // スーパー管理者
        Role::updateOrCreate(['code' => 'super_admin'], [
            'name' => 'Super Admin',
            'description' => 'Full access to all tenants and system settings.'
        ]);

        // テナント管理者
        Role::updateOrCreate(['code' => 'tenant_admin'], [
            'name' => 'Tenant Admin',
            'description' => 'Full access to the assigned tenant data.'
        ]);

        // 閲覧者
        Role::updateOrCreate(['code' => 'viewer'], [
            'name' => 'Viewer',
            'description' => 'Read-only access to the assigned tenant data.'
        ]);
    }
}