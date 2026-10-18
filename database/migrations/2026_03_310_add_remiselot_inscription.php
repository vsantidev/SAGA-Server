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
            $table->boolean('winner_lot')->default(false)->after('rewards');
            $table->integer('winner_lot_pos')->after('winner_lot');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenement_users', function (Blueprint $table) {
             $table->dropColumn('winner_lot');
             $table->dropColumn('winner_lot_pos');
        });
    }
};