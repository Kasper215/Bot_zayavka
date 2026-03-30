<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Если мы на PostgreSQL, используем "чистый" SQL запрос для смены типа
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leads ALTER COLUMN files TYPE JSONB USING files::jsonb');
        } else {
            Schema::table('leads', function (Blueprint $table) {
                $table->json('files')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leads ALTER COLUMN files TYPE TEXT');
        } else {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('files')->nullable()->change();
            });
        }
    }
};
