<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'pricing_type',
        'base_price',
        'annual_fee',
        'included_mails',
        'overage_unit_price',
        'tax_rate',
        'calculation_rule', // 💡 これをキャスト対象にします
        'default_retention_days',
    ];

    // 💡 修正ポイント
    protected $casts = [
        'calculation_rule' => 'json', // または 'array'
        'base_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];
}