<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'discount_type',
        'discount_value',
        'contract_price_override',
        'contract_start_at',
        'contract_end_at',
    ];

    protected $casts = [
        'contract_start_at' => 'date',
        'contract_end_at' => 'date',
        'discount_value' => 'decimal:2',
        'contract_price_override' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}