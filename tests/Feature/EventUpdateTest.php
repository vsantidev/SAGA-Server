<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Fiche convention (create / update) :
// A. titre et dates vérifiés, fin après début ;
// B. « Actif » vaut oui ou non, et la convention active ne se désactive pas seule ;
// C. l'archivage des comptes n'a lieu qu'au passage d'inactive à active.
class EventUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $active;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Admin',
            'email'     => 'eventupdate-admin-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]));
        $this->active = Evenement::where('actif', 1)->firstOrFail();
    }

    private function convention(): Evenement
    {
        $evenement = new Evenement();
        $evenement->title = 'eventupdate-' . uniqid();
        $evenement->site_id = $this->active->site_id;
        $evenement->date_opening = '2030-10-01 09:00:00';
        $evenement->date_ending = '2030-10-03 18:00:00';
        $evenement->actif = 0;
        $evenement->save();

        return $evenement;
    }

    // Membre qui n'est rattaché à aucune convention (créé après l'activation, par exemple)
    private function membreNonRattache(): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Membre',
            'email'     => 'eventupdate-membre-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = 'membre';
        $user->save();

        return $user;
    }

    private function enregistrer(Evenement $evenement, array $changes = [])
    {
        return $this->postJson('/api/event/eventshow/' . $evenement->id, array_merge([
            'id'           => $evenement->id,
            'title'        => $evenement->title,
            'date_opening' => $evenement->date_opening,
            'date_ending'  => $evenement->date_ending,
            'actif'        => $evenement->actif,
        ], $changes));
    }

    // 1 — Règle B : « 2 » ou « oui » sont refusés, la convention reste active
    public function test_actif_must_be_yes_or_no(): void
    {
        $this->enregistrer($this->active, ['actif' => '2'])->assertStatus(422);
        $this->enregistrer($this->active, ['actif' => 'oui'])->assertStatus(422);

        $this->assertSame(1, Evenement::where('actif', 1)->count());
        $this->assertTrue((bool) $this->active->fresh()->actif);
    }

    // 2 — Règle B
    public function test_active_event_cannot_be_deactivated_alone(): void
    {
        $this->enregistrer($this->active, ['actif' => 0])->assertStatus(409);

        $this->assertTrue((bool) $this->active->fresh()->actif);
    }

    // 3 — Règle C : corriger le titre de la convention active n'archive personne
    public function test_saving_active_event_does_not_archive_again(): void
    {
        $membre = $this->membreNonRattache();

        $this->enregistrer($this->active, ['title' => $this->active->title . ' (corrigé)'])->assertStatus(201);

        $this->assertSame('membre', $membre->fresh()->type);
        $this->assertStringEndsWith('(corrigé)', $this->active->fresh()->title);
    }

    // 4 — Règle C : activer une autre convention archive toujours les non-rattachés
    public function test_activating_another_event_still_archives(): void
    {
        $membre = $this->membreNonRattache();
        $suivante = $this->convention();

        $this->enregistrer($suivante, ['actif' => 1])->assertStatus(201);

        $this->assertTrue((bool) $suivante->fresh()->actif);
        $this->assertFalse((bool) $this->active->fresh()->actif);
        $this->assertSame('archive', $membre->fresh()->type);
    }

    // 5 — Règle A, modification
    public function test_update_checks_title_and_dates(): void
    {
        $evenement = $this->convention();

        $this->enregistrer($evenement, ['title' => ''])->assertStatus(422);
        $this->enregistrer($evenement, ['date_opening' => 'demain'])->assertStatus(422);
        $this->enregistrer($evenement, ['date_ending' => '2030-09-30 18:00:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.date_ending.0', 'La date de fin doit être après la date de début.');

        $this->assertSame('2030-10-03 18:00:00', (string) $evenement->fresh()->date_ending);
    }

    // 6 — Règle A, création
    public function test_create_checks_title_and_dates(): void
    {
        $this->postJson('/api/event/eventadd', [
            'title' => '', 'date_opening' => '2030-10-01 09:00:00', 'date_ending' => '2030-10-03 18:00:00',
        ])->assertStatus(422);
        $this->postJson('/api/event/eventadd', [
            'title' => 'eventupdate-fin-avant-debut', 'date_opening' => '2030-10-03 09:00:00', 'date_ending' => '2030-10-01 18:00:00',
        ])->assertStatus(422);

        $this->postJson('/api/event/eventadd', [
            'title' => 'eventupdate-ok', 'date_opening' => '2030-10-01 09:00:00', 'date_ending' => '2030-10-03 18:00:00',
        ])->assertStatus(201);

        $this->assertSame(0, Evenement::where('title', 'eventupdate-fin-avant-debut')->count());
    }
}
