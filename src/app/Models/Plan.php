<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // 正しいインポート

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'pricing_type',
        'base_price',
        'annual_fee',
        'included_mails',
        'unit_price',           
        'included_units',       
        'overage_unit_price',
        'tax_rate',
        'calculation_rule',
        'default_retention_days',
    ];

    protected $casts = [
        'calculation_rule'   => 'array',     // jsonよりarrayの方が扱いやすいです
        'base_price'         => 'decimal:2',
        'unit_price'         => 'decimal:2', 
        'overage_unit_price' => 'decimal:2', 
        'tax_rate'           => 'decimal:2',
    ];

    /**
     * プランは多くの「テナント契約（TenantPlan）」に紐付く
     */
    public function tenantPlans(): HasMany
    {
        return $this->hasMany(TenantPlan::class);
    }
}