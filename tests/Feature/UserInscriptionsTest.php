<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\TimeSlot;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// « Mes inscriptions » (getUserInscriptions) : chaque créneau porte son état de calcul,
// et les créneaux arrivent dans l'ordre chronologique.
class UserInscriptionsTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $joueur;
    private User $animateur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->joueur = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Mes inscriptions',
            'email'     => 'mes-inscr-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $this->animateur = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Animateur',
            'email'     => 'mes-inscr-anim-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($this->joueur);
    }

    private function slot(string $start, string $drawStatus): TimeSlot
    {
        return TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => "Créneau $start",
            'start_time'   => $start,
            'end_time'     => date('Y-m-d H:i:s', strtotime($start) + 7200),
            'draw_status'  => $drawStatus,
        ]);
    }

    private function wish(TimeSlot $slot, string $status, int $weight = 1): void
    {
        $animation = Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->animateur->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $slot->id,
            'open_time'         => $slot->start_time,
            'closed_time'       => $slot->end_time,
            'time'              => 2,
            'capacity'          => 5,
        ]);
        Inscription::create([
            'user_id'       => $this->joueur->id,
            'animation_id'  => $animation->id,
            'weight'        => $weight,
            'status'        => $status,
            'registered_at' => now(),
        ]);
    }

    private function mesInscriptions(): array
    {
        return $this->getJson('/api/inscription/userInscriptions?user_id=' . $this->joueur->id)
            ->assertStatus(200)
            ->json('inscriptions');
    }

    public function test_time_slot_carries_its_draw_status(): void
    {
        $calcule = $this->slot('2026-11-28 14:00:00', 'drawn');
        $this->wish($calcule, 'confirmed', 1);
        $this->wish($calcule, 'cascade_cancelled', 2);

        $items = $this->mesInscriptions();

        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertSame('drawn', $item['time_slot']['draw_status']);
        }
    }

    // Le créneau du soir créé en premier ne doit pas passer devant celui du matin
    public function test_time_slots_are_in_chronological_order(): void
    {
        $soir = $this->slot('2026-11-28 20:00:00', 'open');
        $matin = $this->slot('2026-11-28 09:00:00', 'open');
        $this->wish($soir, 'pending');
        $this->wish($matin, 'pending');

        $items = $this->mesInscriptions();

        $this->assertSame([$matin->id, $soir->id], array_column(array_column($items, 'time_slot'), 'id'));
    }
}
