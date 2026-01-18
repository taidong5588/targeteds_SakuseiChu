<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminUser;
use App\Models\AdminRole;
use App\Models\Language;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ なければ作成する (firstOrCreate) 方式に変更
        $role = AdminRole::firstOrCreate(
            ['code' => 'system_admin'],
            ['name' => 'システム管理者']
        );

        $language = Language::firstOrCreate(
            ['code' => 'ja'],
            ['name' => '日本語']
        );

        // これで例外を投げずに確実にユーザーが作成されます
        AdminUser::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'          => 'System Admin',
                'password'      => Hash::make('password'),
                'admin_role_id' => $role->id,
                'language_id'   => $language->id,
            ]
        );
        
        $this->command->info('Admin user created successfully.');
    }
}