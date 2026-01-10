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
        Schema::create('languages', function (Blueprint $table) {
            $table->id()->comment('言語ID');
            $table->string('name')->comment('言語名');
            $table->string('code', 10)->unique()->comment('言語コード(ja,en,zh_CN,ko)');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
            
            $table->comment('多言語管理マスタ：システム対応言語を定義');
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