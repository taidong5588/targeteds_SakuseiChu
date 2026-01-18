<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tenant_plans', function (Blueprint $table) {
            $table->id();
            
            // 💡 外部キー
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('契約先テナント');
            $table->foreignId('plan_id')->constrained()->comment('選択されたプラン');
            
            // 💡 個別割引設定 (->change() を削除)
            $table->string('discount_type')->default('none')->comment('割引種類: none, rate, fixed');
            $table->decimal('discount_value', 12, 2)->default(0)->comment('割引額または率');
            
            // 💡 特約
            $table->decimal('contract_price_override', 12, 2)->nullable()->comment('個別設定基本料金');
            
            // 💡 契約期間
            $table->date('contract_start_at')->comment('契約開始日');
            $table->date('contract_end_at')->nullable()->comment('契約終了日');
            
            // // 課金状態
            // $table->enum('status', ['active', 'trial', 'canceled', 'expired'])->index()->comment('課金状態');

            // // 課金・支払い管理
            // $table->string('payment_provider_customer_id')->nullable()->comment('決済プロバイダの顧客ID（暗号化保存）');
            // $table->string('payment_provider_contract_id')->nullable()->comment('決済プロバイダのサブスクリプションID（暗号化保存）');       
            
            $table->timestamps();
            $table->softDeletes()->comment('論理削除日時');
            
        });
    }

    public function down(): void {
        Schema::dropIfExists('tenant_plans');
    }
};
