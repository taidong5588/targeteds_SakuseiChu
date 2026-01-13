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
            $table->string('key')->unique();

            /**
             * URL / 識別用スラッグ（将来公開APIなどに使用可）
             */
            $table->string('slug')->unique();

            /**
             * 管理画面表示名（人が理解する名称）
             * 例: トライアル終了7日前通知
             */
            $table->string('title')->comment('テンプレート名');

            /**
             * 通知チャネル
             * mail / slack / web など
             */
            $table->string('channel')->default('mail');

            /**
             * メール件名（mail以外では null 可）
             */
            $table->string('subject')->nullable();

            /**
             * 本文（HTML可）
             * 変数例:
             *  {notify_name}
             *  {tenant_name}
             *  {expiry_date}
             */
            $table->longText('body');

            /**
             * 本文内で使用できる変数のリスト
             * 例: ["notify_name", "tenant_name", "expiry_date"]
             */
            $table->json('allowed_variables')->nullable()->comment('本文内で使用できる変数のリスト');

            /**
             * 有効 / 無効フラグ
             */
            $table->boolean('is_active')->default(true);

            /**
             * memo（管理者向け説明文）
             */
            $table->text('memo')
                ->nullable()
                ->comment('memo（管理者向け説明文）');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notify_mail_templates');
    }
};
