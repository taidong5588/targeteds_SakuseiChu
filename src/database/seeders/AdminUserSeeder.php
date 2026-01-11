<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminUser;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Language;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('code', 'super_admin')->first();
        $tenant = Tenant::where('code', 'internal')->first();
        $language = Language::where('code', 'ja')->first();

        if (!$role || !$tenant || !$language) {
            throw new \Exception('Required master data not found for AdminUserSeeder.');
        }

        AdminUser::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'        => 'System Admin',
                'password'    => Hash::make('password'),
                // 'tenant_id'   => $tenant->id,
                'role_id'     => $role->id,
                'language_id' => $language->id,
            ]
        );
    }
}
