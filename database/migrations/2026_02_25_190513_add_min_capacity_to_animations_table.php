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
        Schema::table('animations', function (Blueprint $table) {
            $table->integer('min_capacity')->nullable()->after('capacity');
            $table->string('system')->nullable()->after('min_capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animations', function (Blueprint $table) {
            $table->dropColumn(['min_capacity', 'system']);
        });
    }
};