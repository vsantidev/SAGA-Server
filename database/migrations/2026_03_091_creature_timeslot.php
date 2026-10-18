<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Création de la table time_slots
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->string('name')->nullable();             // ex: "Matin", "Après-midi"
            $table->datetime('start_time');                 // heure de début du créneau
            $table->datetime('end_time')->nullable();       // heure de fin du créneau
            $table->string('draw_status')->default('open'); // open | closed | drawn
            $table->timestamp('drawn_at')->nullable();      // date du tirage
            $table->timestamps();
        });

        // Ajout de la clé étrangère sur animations
        Schema::table('animations', function (Blueprint $table) {
            $table->foreignId('time_slot_id')->nullable()->constrained('time_slots')->nullOnDelete()->after('evenement_id');
        });
    }

    public function down(): void
    {
        // Ordre inverse : d'abord la FK sur animations
        Schema::table('animations', function (Blueprint $table) {
            $table->dropForeign(['time_slot_id']);
            $table->dropColumn('time_slot_id');
        });

        Schema::dropIfExists('time_slots');
    }
};