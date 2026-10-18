<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoomStoreTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsUser(): void
    {
        Sanctum::actingAs(User::create([
            'lastname'  => 'Test',
            'firstname' => 'Room',
            'email'     => 'room-store-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]));
    }

    // Une salle créée avec seulement un nom est rattachée au site de la
    // convention active, avec pmr à false par défaut.
    public function test_store_room_with_name_only_uses_active_event_site(): void
    {
        $this->actingAsUser();
        $siteId = Evenement::where('actif', 1)->value('site_id');

        $response = $this->postJson('/api/rooms/rooms', ['name' => 'Salle de test']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('rooms', [
            'id'      => $response->json('room.id'),
            'name'    => 'Salle de test',
            'site_id' => $siteId,
            'pmr'     => 0,
        ]);
    }

    // Sans convention active ni site_id fourni, l'API répond 422 au lieu de planter.
    public function test_store_room_without_active_event_returns_422(): void
    {
        $this->actingAsUser();
        Evenement::where('actif', 1)->update(['actif' => 0]);

        $this->postJson('/api/rooms/rooms', ['name' => 'Salle de test'])
            ->assertStatus(422)
            ->assertJson(['status' => 'false']);
    }
}
