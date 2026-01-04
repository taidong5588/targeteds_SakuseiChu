<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['code' => 'internal'],
            [
                'name' => 'Internal Admin',
                'plan' => 'internal',
                'status' => 'active',
                'default_locale' => 'ja',
                'mail_enabled' => false,
            ]
        );
    }
}
