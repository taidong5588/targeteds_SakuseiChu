<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\NotifyMailService;
use Carbon\Carbon;

class SendTenantAlerts extends Command
{
    protected $signature = 'notify:send-tenant-ending-alerts';
    protected $description = 'トライアル・契約期限通知を自動送信します';

    public function handle(): int
    {
        Tenant::where('is_active', true)->with('tenantPlan')->each(function (Tenant $tenant) {
            $now = Carbon::now()->startOfDay();

            // 1. トライアル終了3日前
            if ($tenant->trial_ends_at && $now->diffInDays($tenant->trial_ends_at->startOfDay(), false) === 3) {
                NotifyMailService::send('trial_3days', $tenant);
            }

            // 2. 本契約終了7日前
            $contractEnd = $tenant->tenantPlan?->contract_end_at;
            if ($contractEnd && $now->diffInDays($contractEnd->startOfDay(), false) === 7) {
                NotifyMailService::send('contract_before_7days', $tenant);
            }
        });

        $this->info('Alert notifications sent successfully.');
        return Command::SUCCESS;
    }
}