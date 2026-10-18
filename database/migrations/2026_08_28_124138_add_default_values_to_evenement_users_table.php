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
        Schema::table('evenement_users', function (Blueprint $table) {
            $table->boolean('winner_lot')->default(false)->change();
            $table->integer('winner_lot_pos')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenement_users', function (Blueprint $table) {
            $table->boolean('winner_lot')->default(null)->change();
            $table->integer('winner_lot_pos')->default(null)->change();
        });
    }
};
