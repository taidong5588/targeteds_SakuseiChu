<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 通知テンプレート管理テーブル
     */
    public function up(): void
    {
        Schema::create('notify_mail_templates', function (Blueprint $table) {
            $table->id();

            /**
             * システム内部キー（コード・判定用）
             * 例:
             *  - trial_7days
             *  - trial_3days
             *  - contract_before
             *  - contract_after
             */
            $table->string('key')->unique()->comment('システム内部キー');

            $table->string('slug')->unique()->comment('URL / 識別用スラッグ');
            $table->string('title')->comment('テンプレート名');
            $table->enum('channel', ['mail', 'slack', 'web'])->default('mail')->comment('通知チャネル');
            $table->string('subject')->nullable()->comment('メール件名');
            $table->longText('body')->nullable()->comment('テンプレート本文');

            /**
             * 本文内で使用できる変数のリスト
             * 例: ["notify_name", "tenant_name", "expiry_date"]
             */
            $table->json('allowed_variables')->nullable()->comment('本文内で使用できる変数のリスト');
            $table->text('memo')->nullable()->comment('memo（管理者向け説明文）');

            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notify_mail_templates');
    }
};
