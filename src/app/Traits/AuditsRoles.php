<?php

namespace App\Traits;

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * 🛡️ AdminUser の権限（ロール）変更を監査ログに記録するトレイト
 * - AdminUser モデルに適用することで、role カラムの変更を特定のアクションとして記録
 */
trait AuditsRoles
{
    // モデルの一時的なプロパティを格納するための配列
    // これにより、EloquentがこれをDBカラムとして扱わないようにする
    protected array $_auditsRolesInternalData = [];

    /**
     * トレイトが適用されたモデルのブート時に実行されるメソッド
     *
     * @return void
     */
    public static function bootAuditsRoles(): void
    {
        // モデルが更新される直前 (updating イベント) に古いロールの値をキャッチ
        static::updating(function ($model) {
            // Adminガードでログインしている場合のみ処理
            if (!Auth::guard('admin')->check()) {
                return;
            }

            // 'role' カラムが変更対象なら、古い値を一時プロパティに保存
            // 'role'はFilamentフォームで使われているカラム名と仮定
            if ($model->isDirty('role') || $model->isDirty('role_id') || $model->isDirty('roles')) {
                // _auditsRolesInternalData 配列に保存
                $model->_auditsRolesInternalData['old_role'] = $model->getOriginal('role') ?? 'N/A';
            }
        });

        // モデルが更新された直後 (updated イベント) にログを保存
        static::updated(function ($model) {
            // Adminガードでログインしている場合のみ処理
            if (!Auth::guard('admin')->check()) {
                return;
            }

            // _auditsRolesInternalData から古いロールの値を取得
            if (isset($model->_auditsRolesInternalData['old_role'])) {
                $oldRole = $model->_auditsRolesInternalData['old_role'];

                AdminAuditLog::create([
                    'admin_user_id' => Auth::guard('admin')->id(),
                    // AdminUserは特定のテナントに紐づかないケースが多いのでnullを許容
                    'tenant_id'     => $model->tenant_id ?? null,
                    'action'        => 'role_changed', // 権限変更専用のアクション
                    'target_type'   => get_class($model),
                    'target_id'     => (string)$model->getKey(),
                    'before'        => ['role' => $oldRole],
                    // 更新後のroleカラムの値を取得
                    'after'         => ['role' => $model->role ?? 'N/A'],
                    'ip'            => Request::ip(),
                    'user_agent'    => Request::userAgent(),
                    'occurred_at'   => now(),
                ]);
            }
            // 処理後、一時プロパティをクリア
            $model->_auditsRolesInternalData = [];
        });
    }
}
