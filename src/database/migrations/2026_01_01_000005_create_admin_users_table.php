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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id()->comment('システム管理者ID');

            $table->string('name')->comment('管理者名');
            $table->string('email')->unique()->comment('ログインメール');
            $table->string('password')->comment('ハッシュ化パスワード');

            // // 🏢 所属テナント
            // $table->foreignId('tenant_id')
            //     ->nullable()
            //     ->constrained('tenants')
            //     ->restrictOnDelete()
            //     ->comment('所属テナントID');

            // 🔑 管理者ロール
            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->restrictOnDelete()
                ->comment('役割ID');

            // 🌍 表示言語
            $table->foreignId('language_id')
                ->nullable()
                ->constrained('languages')
                ->restrictOnDelete()
                ->comment('優先言語ID');

            $table->string('role')->default('super_admin')->comment('権限種別');
            $table->string('locale', 10)->default('ja')->comment('管理画面の表示言語');                

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
