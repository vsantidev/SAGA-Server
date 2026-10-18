<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Suppression d'une convention depuis la liste (destroy) :
// A. une convention inactive sans animation ni compte rattaché est supprimée, avec ses créneaux ;
// B. une convention qui a des comptes rattachés ou des animations est refusée
//    en 409, avec un message et les nombres, et rien n'est supprimé ;
// C. la convention active n'est jamais supprimée, même vide.
class EventDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Admin',
            'email'     => 'eventdelete-admin-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($this->admin);
    }

    private function convention(int $actif = 0): Evenement
    {
        $active = Evenement::where('actif', 1)->firstOrFail();

        $evenement = new Evenement();
        $evenement->title = 'eventdelete-' . uniqid();
        $evenement->site_id = $active->site_id;
        $evenement->date_opening = '2030-10-01 09:00:00';
        $evenement->date_ending = '2030-10-03 18:00:00';
        $evenement->actif = $actif;
        $evenement->save();

        return $evenement;
    }

    private function supprimer(int $id)
    {
        return $this->deleteJson('/api/event/eventlist', ['id' => $id]);
    }

    // 1 — Règle A
    public function test_empty_inactive_event_is_deleted(): void
    {
        $evenement = $this->convention();

        $this->supprimer($evenement->id)->assertStatus(200);

        $this->assertDatabaseMissing('evenements', ['id' => $evenement->id]);
    }

    // 2 — Règle B : un compte rattaché (participant importé, admin ajouté à l'activation)
    public function test_event_with_participants_is_refused_with_counts(): void
    {
        $evenement = $this->convention();
        DB::table('evenement_users')->insert(['evenement_id' => $evenement->id, 'user_id' => $this->admin->id]);

        $this->supprimer($evenement->id)
            ->assertStatus(409)
            ->assertJsonPath('counts.comptes', 1)
            ->assertJsonPath('counts.animations', 0)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('evenements', ['id' => $evenement->id]);
    }

    // 3 — Règle A : les créneaux (découpage des demi-journées) ne bloquent pas, ils partent avec la convention
    public function test_event_with_time_slots_only_is_deleted_with_its_slots(): void
    {
        $evenement = $this->convention();
        DB::table('time_slots')->insert([
            ['evenement_id' => $evenement->id, 'name' => 'Samedi matin', 'start_time' => '2030-10-01 09:00:00'],
            ['evenement_id' => $evenement->id, 'name' => 'Samedi soir', 'start_time' => '2030-10-01 20:00:00'],
        ]);

        $this->supprimer($evenement->id)->assertStatus(200);

        $this->assertDatabaseMissing('evenements', ['id' => $evenement->id]);
        $this->assertSame(0, DB::table('time_slots')->where('evenement_id', $evenement->id)->count());
    }

    // 4 — Règle B : refus déjà en place pour les animations, en 409 au lieu de 520
    public function test_event_with_animations_is_refused_with_409(): void
    {
        $evenement = $this->convention();
        DB::table('animations')->insert([
            'evenement_id'      => $evenement->id,
            'user_id'           => $this->admin->id,
            'type_animation_id' => DB::table('type_animations')->value('id'),
            'title'             => 'eventdelete-animation',
            'content'           => 'test',
        ]);

        $this->supprimer($evenement->id)
            ->assertStatus(409)
            ->assertJsonPath('counts.animations', 1);

        $this->assertDatabaseHas('evenements', ['id' => $evenement->id]);
    }

    // 5 — Règle C
    public function test_active_event_is_never_deleted_even_empty(): void
    {
        $evenement = $this->convention(1);

        $this->supprimer($evenement->id)
            ->assertStatus(409)
            ->assertJson(['message' => 'Impossible de supprimer la convention active.']);

        $this->assertDatabaseHas('evenements', ['id' => $evenement->id]);
    }

    public function test_unknown_event_returns_404(): void
    {
        $this->supprimer(999999999)->assertStatus(404);
    }
}
