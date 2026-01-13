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
        'plan_id', 
        'language_id', 
        'name', 
        'code', 
        'domain',
        'notify_name',
        'notify_email',
        'is_active', 
        'trial_start_at', 
        'trial_ends_at', 
        'audit_log_retention_days',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'trial_start_at' => 'datetime',
        'trial_ends_at'  => 'datetime',
        // 🚀 個人情報保護：DB上では暗号化、取得時に自動復号
        'notify_name' => 'encrypted',
        'notify_email' => 'encrypted',
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
            // 🚀 【重要】保存時にリレーション先から plan_id を親にコピーする
            // これにより DB の plan_id カラムが埋まり、エラーを回避しつつ整合性を保ちます
            if ($tenant->tenantPlan && $tenant->tenantPlan->plan_id) {
                $tenant->plan_id = $tenant->tenantPlan->plan_id;
            }

            // 安全装置：日付の前後関係
            if ($tenant->trial_start_at && $tenant->trial_ends_at) {
                if ($tenant->trial_start_at->gt($tenant->trial_ends_at)) {
                    $tenant->trial_ends_at = $tenant->trial_start_at;
                }
            }

            // 期限切れ時の自動OFF
            if ($tenant->contractState() === 'expired') {
                $tenant->is_active = false;
            }
        });
    }

    /**
     * 📊 ステータスごとの件数・売上を一括取得（Widget専用）
     * - DB条件ではなく、contractState() を唯一の正とする
     * - N+1 回避のため eager load
     */
    public static function getStateStats(): array
    {
        $tenants = self::with(['tenantPlan', 'plan'])->get();

        return [
            'active' => $tenants->filter(
                fn ($t) => $t->contractState() === 'active'
            )->count(),

            'trial_critical' => $tenants->filter(
                fn ($t) => $t->contractState() === 'trial_critical'
            )->count(),

            'trial_warning' => $tenants->filter(
                fn ($t) => $t->contractState() === 'trial_warning'
            )->count(),

            'expired' => $tenants->filter(
                fn ($t) => $t->contractState() === 'expired'
            )->count(),

            'upcoming' => $tenants->filter(
                fn ($t) => $t->contractState() === 'upcoming'
            )->count(),

            // 💰 月次予測売上（稼働中＋要対応トライアルのみ）
            'total_revenue' => $tenants
                ->filter(fn ($t) =>
                    in_array($t->contractState(), ['active', 'trial_critical'], true)
                )
                ->sum(fn ($t) => $t->plan?->base_price ?? 0),
        ];
    }

}