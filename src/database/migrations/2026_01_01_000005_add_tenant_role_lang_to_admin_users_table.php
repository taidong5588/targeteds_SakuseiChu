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
        Schema::table('admin_users', function (Blueprint $table) {
            // 🏢 どの会社の人か？（外部キー）
            $table->foreignId('tenant_id')->nullable()
                ->constrained('tenants')->onDelete('restrict')
                ->comment('所属テナントID');

            // 🔑 どんな権限を持っているか？（外部キー）
            $table->foreignId('role_id')->nullable()->after('tenant_id')
                ->constrained('roles')->onDelete('restrict')
                ->comment('役割ID');

            // 🌍 何語で画面を表示するか？（外部キー）
            $table->foreignId('language_id')->nullable()->after('role_id')
                ->constrained('languages')->onDelete('restrict')
                ->comment('優先言語ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['language_id']);
            $table->dropColumn(['tenant_id', 'role_id', 'language_id']);
        });
    }
};