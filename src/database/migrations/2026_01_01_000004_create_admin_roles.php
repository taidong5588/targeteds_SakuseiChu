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
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id()->comment('ロールID');
            $table->string('name')->unique()->comment('ロール名（翻訳キー：Super Admin等）');
            $table->string('code')->unique()->comment('ロールコード（例：super_admin）');
            $table->text('description')->nullable()->comment('役割の説明（翻訳キー）');
            $table->timestamps();

            $table->comment('権限管理マスタ：ユーザーの権限レベルを定義');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};