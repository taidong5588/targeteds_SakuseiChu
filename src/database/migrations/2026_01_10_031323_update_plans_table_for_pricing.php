<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * プランテーブルの拡張
     */
    public function up(): void {
        Schema::table('plans', function (Blueprint $table) {
            // 💡 pricing_type がない場合のみ追加
            if (!Schema::hasColumn('plans', 'pricing_type')) {
                $table->string('pricing_type')->default('bundle')->after('name')->comment('課金形式');
            }

            // 💡 base_price がない場合のみ追加
            if (!Schema::hasColumn('plans', 'base_price')) {
                $table->decimal('base_price', 12, 2)->default(0)->after('pricing_type')->comment('標準基本料金');
            }

            // 💡 unit_price がない場合のみ追加
            if (!Schema::hasColumn('plans', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('base_price')->comment('超過1通あたりの単価');
            }

            // 💡 included_units がない場合のみ追加
            if (!Schema::hasColumn('plans', 'included_units')) {
                $table->integer('included_units')->default(0)->after('unit_price')->comment('無料枠の通数');
            }

            // 💡 tax_rate がない場合のみ追加
            if (!Schema::hasColumn('plans', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(10.00)->after('included_units')->comment('消費税率(%)');
            }

            // 💡 calculation_rule がない場合のみ追加
            if (!Schema::hasColumn('plans', 'calculation_rule')) {
                $table->json('calculation_rule')->nullable()->after('tax_rate')->comment('特殊計算用JSON');
            }
        });
    }

    /**
     * ロールバック処理
     */
    public function down(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_type', 
                'base_price', 
                'unit_price', 
                'included_units', 
                'tax_rate', 
                'calculation_rule'
            ]);
        });
    }
};