<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AdminAuditLog
 *
 * 管理者操作の証跡を保存するモデル
 * - 削除禁止（論理削除すらしない想定）
 * - 参照専用（update/delete しない）
 */
class AdminAuditLog extends Model
{
    /**
     * テーブル名
     *
     * @var string
     */
    protected $table = 'admin_audit_logs';

    /**
     * 書き込み可能カラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_user_id',
        'tenant_id', 
        'action',
        'target_type',
        'target_id',
        'before',
        'after',
        'ip',
        'user_agent',
        'occurred_at',
    ];

    /**
     * JSON カラムのキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * 管理者とのリレーション
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    /**
     * テナントとのリレーション
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
