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
        Schema::create('plans', function (Blueprint $table) {
            $table->id()->comment('プランID');
            $table->string('name')->comment('プラン名（例：ベーシック1000）');
            $table->string('code')->unique()->comment('プランコード（例：bundle_1000）');
            $table->string('pricing_type')->default('bundle')->comment('課金タイプ（パック型、従量型など）');
            $table->integer('annual_fee')->default(0)->comment('年間基本料金');
            $table->integer('included_mails')->default(0)->comment('内包メール数');
            $table->integer('unit_price')->default(0)->comment('1通あたりの単価');
            $table->integer('overage_unit_price')->default(0)->comment('超過分単価');
            $table->decimal('tax_rate', 5, 2)->default(10.00)->comment('消費税率');
            $table->integer('default_retention_days')->default(90)->comment('標準のログ保持期間');
            $table->timestamps();

            $table->comment('料金プランマスタ：各テナントの課金体系と初期設定を管理');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};