<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plan_id', 'language_id', 'name', 'code', 'domain',
        'is_active', 'trial_start_at', 'trial_ends_at', 'audit_log_retention_days',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'trial_start_at' => 'datetime',
        'trial_ends_at'  => 'datetime',
    ];

    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function language(): BelongsTo { return $this->belongsTo(Language::class); }
    public function tenantPlan(): HasOne { return $this->hasOne(TenantPlan::class); }

    /**
     * 💯 契約ステータス判定（本番用）
     */
    public function contractState(): string
    {
        $today = now()->startOfDay();
        $plan = $this->tenantPlan;

        // 1. 本契約チェック
        if ($plan && $plan->contract_start_at && $plan->contract_end_at) {
            $cStart = $plan->contract_start_at->startOfDay();
            $cEnd   = $plan->contract_end_at->startOfDay();

            if ($cEnd->lt($today)) return 'expired';
            if ($cStart->gt($today)) return 'upcoming';
            return 'active';
        }

        // 2. トライアルチェック
        if ($this->trial_ends_at) {
            $tStart = $this->trial_start_at?->startOfDay();
            $tEnd   = $this->trial_ends_at->startOfDay();
            
            if ($tEnd->lt($today)) return 'expired';
            if ($tStart && $tStart->gt($today)) return 'upcoming';

            $days = $today->diffInDays($tEnd, false);
            if ($days <= 3) return 'trial_critical';
            if ($days <= 7) return 'trial_warning';
            return 'active';
        }

        return 'inactive';
    }

    protected static function booted()
    {
        static::saving(function (Tenant $tenant) {
            // 💡 期限切れ(expired)の場合のみ、is_activeを強制的にfalseにする
            // それ以外（active等）は、管理者が画面で選んだToggleの状態が維持されます。
            if ($tenant->contractState() === 'expired') {
                $tenant->is_active = false;
            }
        });
    }
}