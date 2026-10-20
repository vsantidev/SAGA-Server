<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Room;
use App\Models\TimeSlot;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Calendrier (animationListValidate) : les animations arrivent dans l'ordre chronologique
// des créneaux, quel que soit leur ordre de création.
class AnimationCalendarOrderTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $auteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->auteur = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Calendrier',
            'email'     => 'anim-calendar-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($this->auteur);
    }

    private function animationOnNewSlot(string $start): Animation
    {
        $slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => $start,
            'end_time'     => date('Y-m-d H:i:s', strtotime($start) + 7200),
            'draw_status'  => 'open',
        ]);
        $animation = Animation::create([
            'title'             => 'Calendrier ' . $start,
            'content'           => 'Contenu',
            'user_id'           => $this->auteur->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $slot->id,
            'room_id'           => Room::where('active', 1)->value('id'),
            'open_time'         => $start,
            'closed_time'       => $slot->end_time,
            'time'              => 2,
            'capacity'          => 6,
        ]);
        // 'validate' n'est pas mass-assignable : on le pose directement.
        $animation->validate = true;
        $animation->save();

        return $animation;
    }

    // L'après-midi créé avant le matin du même jour ne doit pas passer devant
    public function test_animations_come_in_slot_order(): void
    {
        $apresMidi = $this->animationOnNewSlot('2026-11-28 15:00:00');
        $matin = $this->animationOnNewSlot('2026-11-28 07:30:00');

        $ids = array_column(
            $this->getJson('/api/animation/animationListValidate?param1=' . $this->auteur->id)
                ->assertStatus(200)
                ->json('listeAnimation'),
            'id'
        );

        $this->assertLessThan(array_search($apresMidi->id, $ids), array_search($matin->id, $ids));

        $starts = array_column(
            $this->getJson('/api/animation/animationListValidate?param1=' . $this->auteur->id)->json('listeAnimation'),
            'slot_start_time'
        );
        $sorted = $starts;
        sort($sorted);
        $this->assertSame($sorted, $starts);
    }
}
