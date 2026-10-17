<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Le code utilise 'pending' comme statut initial d'une inscription,
     * alors que la colonne avait 'en_attente' comme valeur par défaut.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE inscriptions ALTER COLUMN status SET DEFAULT 'pending'");

        DB::table('inscriptions')
            ->where('status', 'en_attente')
            ->update(['status' => 'pending']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE inscriptions ALTER COLUMN status SET DEFAULT 'en_attente'");
    }
};
