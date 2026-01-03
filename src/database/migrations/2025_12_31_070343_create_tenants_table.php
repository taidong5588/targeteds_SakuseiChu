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
            $table->string('name')->comment('企業名');
            $table->string('code')->unique()->comment('識別コード');
            $table->string('domain')->nullable()->comment('専用ドメイン');
            $table->string('plan')->default('free')->comment('契約プラン');
            $table->string('default_locale')->default('ja')->comment('既定言語');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
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
