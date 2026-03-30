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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fio_from_telegram', 'telegram_chat_id', 'region']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fio_from_telegram')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('region')->nullable();
        });
    }
};
