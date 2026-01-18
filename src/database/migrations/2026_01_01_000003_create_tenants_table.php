<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id()->comment('テナントID');
            
            // 基本情報
            $table->string('name')->comment('企業名');
            $table->string('code')->unique()->comment('識別コード（URLや内部処理用）');
            $table->string('domain')->nullable()->comment('専用ドメイン（将来の独自ドメイン対応用）');

            $table->string('notify_name')->nullable()->comment('担当者名（暗号化保存）');
            $table->string('notify_email')->nullable()->comment('メールアドレス（暗号化保存）');          
            
            // 状態と期間管理
            $table->boolean('is_active')->default(true)->comment('有効フラグ（falseで全停止）');
            $table->timestamp('trial_start_at')->nullable()->comment('トライアル開始日時');
            $table->timestamp('trial_ends_at')->nullable()->comment('トライアル終了日時');

            // リレーション
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('restrict')->comment('契約プランID');
            $table->foreignId('language_id')->constrained('languages')->onDelete('restrict')->comment('規定言語ID');
                        
            $table->string('mail_from_address')->nullable()->comment('送信元メールアドレス（暗号化保存）');
            $table->string('mail_from_name')->nullable()->comment('送信元名（暗号化保存）');

           // SMTP（必要な場合のみ）
            $table->string('smtp_host')->nullable()->comment('SMTPホスト（暗号化保存）');
            $table->integer('smtp_port')->nullable()->comment('SMTPポート（暗号化保存）');
            $table->string('smtp_username')->nullable()->comment('SMTPユーザー名（暗号化保存）');
            $table->string('smtp_password')->nullable()->comment('SMTPパスワード（暗号化保存）');
            $table->boolean('smtp_encryption')->default(false)->comment('SMTP暗号化（SSL/TLS）使用フラグ');            
            
            // 監査ログ設定
            $table->integer('audit_log_retention_days')->default(90)->comment('監査ログ保持期間（日）');
            
            $table->timestamps();
            $table->softDeletes()->comment('論理削除日時');

            $table->comment('テナント管理テーブル：各顧客企業の契約・状態を管理する最重要テーブル');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
