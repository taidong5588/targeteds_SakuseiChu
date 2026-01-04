<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 管理者操作の監査ログテーブル作成
     *
     * - 外販向け / 監査 / インシデント対応 必須
     * - 「誰が・いつ・何を・どう変更したか」を完全に記録
     */
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {

            // 監査ログID
            $table->id()->comment('監査ログID');

            // 操作した管理者（admin_users.id）
            $table->foreignId('admin_user_id')
                ->nullable()
                ->constrained('admin_users') // admin_users テーブルに外部キー制約
                ->nullOnDelete() // AdminUserが削除されたらnullにする
                ->comment('操作した管理者ID（admin_users）');

            // 対象テナント（tenants.id）
            // 操作対象のモデルが属するテナント、またはTenantモデル自身のID
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants') // tenants テーブルに外部キー制約
                ->nullOnDelete() // Tenantが削除されたらnullにする
                ->comment('操作対象が属するテナントID（tenants）');

            // 操作種別（create / update / delete / login 等）
            $table->string('action')
                ->comment('操作内容（create / update / delete 等）');

            // 操作対象のモデルクラス名（例：App\Models\User, App\Models\Tenant）
            $table->string('target_type')
                ->nullable()
                ->comment('操作対象モデルのFQCN');

            // 操作対象のID
            $table->string('target_id')
                ->nullable()
                ->comment('操作対象レコードID');

            // 変更前データ（JSON）
            $table->json('before')
                ->nullable()
                ->comment('変更前データ（JSON形式）');

            // 変更後データ（JSON）
            $table->json('after')
                ->nullable()
                ->comment('変更後データ（JSON形式）');

            // 操作元IPアドレス
            $table->ipAddress('ip')
                ->nullable()
                ->comment('操作元IPアドレス');

            // 操作時のユーザーエージェント
            $table->string('user_agent', 500) // 最大長を500に設定
                ->nullable()
                ->comment('ブラウザ・端末情報');

            // 操作発生日時（created_at とは分離）
            $table->timestamp('occurred_at')
                ->comment('操作発生日時');

            // Laravel標準タイムスタンプ（検索・管理用）
            $table->timestamps();
        });
    }

    /**
     * ロールバック時：監査ログ削除
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
