<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_token_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('url')->default('/');
            $table->string('icon')->default('/pwa-icon.png');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['device_token_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_notifications');
    }
};
