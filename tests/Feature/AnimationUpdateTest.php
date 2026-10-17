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

// Règles de animationUpdate :
// A. une animation validée doit avoir un créneau ;
// B. la salle est vérifiée dès que le créneau change, que la salle change ou que l'animation passe en validée ;
// C. une animation d'une convention passée n'est pas modifiable.
class AnimationUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $author;
    private TimeSlot $slot;
    private Room $room1;
    private Room $room2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->author = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Update',
            'email'     => 'anim-update-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($this->author);

        $this->slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
        ]);
        $this->room1 = $this->makeRoom('Salle test 1');
        $this->room2 = $this->makeRoom('Salle test 2');
    }

    private function makeRoom(string $name): Room
    {
        return Room::create([
            'name'    => $name,
            'pmr'     => false,
            'active'  => true,
            'site_id' => $this->evenement->site_id,
        ]);
    }

    // 'validate' n'est pas mass-assignable : on le pose directement.
    private function makeAnimation(array $attributes, bool $validate): Animation
    {
        $animation = Animation::create(array_merge([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->author->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => 5,
        ], $attributes));
        $animation->validate = $validate;
        $animation->save();

        return $animation->fresh();
    }

    // Reproduit l'appel du front : l'animation complète en JSON dans le champ 'animation'.
    private function update(Animation $animation, array $changes)
    {
        $payload = array_merge($animation->fresh()->toArray(), $changes);

        return $this->postJson('/api/animation/animationShowEdit/' . $animation->id, [
            'animation'  => json_encode($payload),
            'userUpdate' => json_encode(['id' => $this->author->id]),
        ]);
    }

    // 1 — Règle A
    public function test_validating_without_time_slot_is_refused(): void
    {
        $animation = $this->makeAnimation(['room_id' => $this->room1->id], false);

        $this->update($animation, ['validate' => 1])->assertStatus(422);

        $this->assertEquals(0, $animation->fresh()->validate);
    }

    // 2 — Règle B : changement de salle seul
    public function test_changing_only_the_room_to_an_occupied_one_is_refused(): void
    {
        $this->makeAnimation(['room_id' => $this->room2->id, 'time_slot_id' => $this->slot->id], true);
        $animation = $this->makeAnimation(['room_id' => $this->room1->id, 'time_slot_id' => $this->slot->id], true);

        $this->update($animation, ['room_id' => $this->room2->id])
            ->assertStatus(422)
            ->assertJsonStructure(['free_rooms']);

        $this->assertEquals($this->room1->id, $animation->fresh()->room_id);
    }

    // 3 — Règle B : passage en validée
    public function test_validating_when_room_was_taken_meanwhile_is_refused(): void
    {
        $animation = $this->makeAnimation(['room_id' => $this->room2->id, 'time_slot_id' => $this->slot->id], false);
        $this->makeAnimation(['room_id' => $this->room2->id, 'time_slot_id' => $this->slot->id], true);

        $this->update($animation, ['validate' => 1])->assertStatus(422);

        $this->assertEquals(0, $animation->fresh()->validate);
    }

    // 4 — Créneau sans heure de fin
    public function test_assigning_a_time_slot_without_end_time_does_not_crash(): void
    {
        $slotSansFin = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau sans fin',
            'start_time'   => '2026-11-28 14:00:00',
        ]);
        $animation = $this->makeAnimation([
            'room_id'     => $this->room1->id,
            'open_time'   => '2026-11-28 14:00:00',
            'closed_time' => '2026-11-28 16:00:00',
        ], false);

        $this->update($animation, ['time_slot_id' => $slotSansFin->id])->assertStatus(200);

        $this->assertEquals($slotSansFin->id, $animation->fresh()->time_slot_id);
    }

    // 5 — Garde-fou : une modification ordinaire passe, même sur une double réservation déjà en base
    public function test_editing_title_of_validated_animation_still_works(): void
    {
        $this->makeAnimation(['room_id' => $this->room1->id, 'time_slot_id' => $this->slot->id], true);
        $animation = $this->makeAnimation(['room_id' => $this->room1->id, 'time_slot_id' => $this->slot->id], true);

        $this->update($animation, ['title' => 'Nouveau titre'])->assertStatus(200);

        $this->assertSame('Nouveau titre', $animation->fresh()->title);
    }

    // 6 — Règle C
    public function test_animation_of_past_event_cannot_be_modified(): void
    {
        $ancienne = Evenement::where('actif', 0)->firstOrFail();
        $animation = $this->makeAnimation(['evenement_id' => $ancienne->id], true);

        $this->update($animation, ['title' => 'Nouveau titre'])->assertStatus(403);

        $this->assertSame('Animation de test', $animation->fresh()->title);
    }
}
