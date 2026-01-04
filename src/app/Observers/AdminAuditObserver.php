<?php

namespace App\Observers;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * ============================================================
 * 管理画面 操作監査用 Observer
 * ------------------------------------------------------------
 * 目的:
 * - 管理者による全 CRUD 操作を自動で記録する
 * - 外販 / 内部監査 / SOC2 / ISMS を想定した証跡を残す
 * - Filament / Controller 実装に依存しない中央集権的監査
 * ============================================================
 */
class AdminAuditObserver
{
    /**
     * ❌ 監査対象外とするカラム一覧
     *
     * 理由:
     * - updated_at などの自動更新カラムはノイズになる
     * - 本質的な「業務変更内容」を明確にするため
     * - ログ容量削減・監査可読性向上
     */
    protected array $ignoreColumns = [
        'updated_at',
        'created_at',
        'remember_token',
        'last_login_at',
    ];

    /**
     * 🏢 テナントIDの解決ロジック
     *
     * 優先順位:
     * 1. 操作対象モデル自身が tenant_id を持つ場合
     * 2. 操作対象が Tenant モデルそのものの場合
     * 3. ログイン中の管理者が所属する tenant_id
     *
     * → 外販・マルチテナント環境で
     *   「どの会社に対する操作か」を必ず追跡できる
     */
    protected function resolveTenantId(Model $model): ?int
    {
        // ① モデル自身に tenant_id が存在する場合（最優先）
        if (property_exists($model, 'tenant_id')) {
            return $model->tenant_id;
        }

        // ② Tenant モデル自身を操作した場合
        if ($model instanceof \App\Models\Tenant) {
            return $model->id;
        }

        // ③ フォールバック：管理者の所属テナント
        return Auth::guard('admin')->user()->tenant_id ?? null;
    }

    /**
     * 📝 監査ログ共通保存処理
     *
     * 注意点:
     * - admin ガードでログインしている場合のみ記録
     *   → バッチ / API / 一般ユーザー操作は除外
     * - target_type + target_id で操作対象を完全特定
     */
    protected function saveLog(
        Model $model,
        string $action,
        ?array $before = null,
        ?array $after = null
    ): void {
        // 管理画面操作以外は監査対象外
        if (!Auth::guard('admin')->check()) {
            return;
        }

        AdminAuditLog::create([
            'admin_user_id' => Auth::guard('admin')->id(), // 操作した管理者
            'tenant_id'     => $this->resolveTenantId($model), // 対象テナント
            'action'        => $action, // created / updated / deleted
            'target_type'   => get_class($model), // モデルクラス
            'target_id'     => $model->getKey(), // 主キー（型変換しない）
            'before'        => $before, // 変更前データ
            'after'         => $after,  // 変更後データ
            'ip'            => request()->ip(), // 操作元 IP
            'user_agent'    => request()->userAgent(), // 操作端末
            'occurred_at'   => now(), // 操作発生時刻
        ]);
    }

    /**
     * ➕ 新規作成時の監査
     *
     * - after に作成された全データを記録
     * - before は存在しないため null
     */
    public function created(Model $model): void
    {
        $attributes = collect($model->getAttributes())
            ->except($this->ignoreColumns) // ノイズ除外
            ->toArray();

        $this->saveLog($model, 'created', null, $attributes);
    }

    /**
     * ✏️ 更新時の監査
     *
     * ポイント:
     * - 実際に変更されたカラムのみを記録
     * - 「保存したが値は変わっていない」操作は記録しない
     */
    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())
            ->except($this->ignoreColumns)
            ->toArray();

        // 実質変更がない場合はログを残さない
        if (empty($changes)) {
            return;
        }

        // --- 👈 role_id または roles の変更を除外 ---
        // 権限(role_id)の変更が含まれている場合、Trait側(role_changed)で記録するため
        // ここでの通常の 'updated' 記録はスキップする
        if (array_key_exists('role_id', $changes) || array_key_exists('roles', $changes)) {
            return;
        }
        // --------------------


        // before には変更されたカラムの「元の値」のみを入れる
        $before = collect($model->getOriginal())
            ->only(array_keys($changes))
            ->toArray();

        $this->saveLog($model, 'updated', $before, $changes);
    }

    /**
     * ❌ 削除時の監査
     *
     * - 削除直前の全データを before に保存
     * - after は存在しないため null
     */
    public function deleted(Model $model): void
    {
        $before = collect($model->getOriginal())
            ->except($this->ignoreColumns)
            ->toArray();

        $this->saveLog($model, 'deleted', $before, null);
    }
}
