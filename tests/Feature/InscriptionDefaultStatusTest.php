<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InscriptionDefaultStatusTest extends TestCase
{
    use DatabaseTransactions;

    // Une inscription créée sans statut explicite doit être 'pending',
    // valeur utilisée par le tirage et le front (et non 'en_attente').
    public function test_inscription_without_status_defaults_to_pending(): void
    {
        $id = DB::table('inscriptions')->insertGetId([
            'user_id'      => 999999,
            'animation_id' => 999999,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->assertSame('pending', DB::table('inscriptions')->where('id', $id)->value('status'));
    }
}
