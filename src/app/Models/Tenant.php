<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plan_id',
        'language_id',
        'name',
        'code',
        'domain',
        'is_active',
        'trial_start_at',
        'trial_ends_at',
        'audit_log_retention_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_start_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * 契約プランへのリレーション
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * 言語設定へのリレーション
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * テナントが現在利用可能か判定（トライアル期間も考慮）
     */
    public function isActive(): bool
    {
        if (!$this->is_active) return false;
        
        // 本契約済み
        if ($this->subscription_started_at !== null) return true;
        
        // トライアル期間中
        if ($this->trial_ends_at !== null && now()->lessThanOrEqualTo($this->trial_ends_at)) {
            return true;
        }

        return false;
    }

    // テナントは1つの契約（TenantPlan）を持つ
    public function tenantPlan()
    {
        return $this->hasOne(TenantPlan::class);
    }    
}