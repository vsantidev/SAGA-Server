<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Modification d'un créneau (timeslotshow, POST) :
// A. le statut « calculé » (drawn) n'est posé que par le calcul des inscriptions ;
// B. les horaires sont validés après fusion avec les valeurs en base, dans les dates de la convention ;
// C. un créneau qui a des animations ne change pas d'horaires ;
// D. ouvrir / fermer et renommer restent possibles ; modification réservée aux admins.
class TimeSlotUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Convention inactive créée pour le test (annulée avec la transaction)
        $this->evenement = Evenement::create([
            'title'        => 'recette-a15-' . uniqid(),
            'actif'        => 0,
            'date_opening' => '2026-11-27 18:00:00',
            'date_ending'  => '2026-11-29 19:00:00',
            'site_id'      => Evenement::where('actif', 1)->value('site_id'),
        ]);
        $this->admin = $this->user('admin');
        Sanctum::actingAs($this->admin);
    }

    private function user(string $type): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => ucfirst($type),
            'email'     => "timeslot-$type-" . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->save();

        return $user;
    }

    private function slot(array $data = []): TimeSlot
    {
        return TimeSlot::create(array_merge([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Samedi après-midi',
            'start_time'   => '2026-11-28 14:00:00',
            'end_time'     => '2026-11-28 18:00:00',
            'draw_status'  => 'open',
        ], $data));
    }

    private function update(TimeSlot $slot, array $data)
    {
        return $this->postJson("/api/timeslot/timeslotshow/{$slot->id}", $data);
    }

    // 1 — Règle A
    public function test_drawn_status_cannot_be_set_by_update(): void
    {
        $slot = $this->slot();

        $this->update($slot, ['draw_status' => 'drawn'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('draw_status');

        $this->assertSame('open', $slot->fresh()->draw_status);
    }

    // 2 — Règle D : boutons « Désactiver » et « Rouvrir »
    public function test_slot_can_be_closed_and_reopened(): void
    {
        $slot = $this->slot();

        $this->update($slot, ['draw_status' => 'closed'])->assertStatus(200);
        $this->assertSame('closed', $slot->fresh()->draw_status);

        $this->update($slot, ['draw_status' => 'open'])->assertStatus(200);
        $this->assertSame('open', $slot->fresh()->draw_status);
    }

    // 3 — Ouvrir / fermer un créneau qui a des animations ne touche pas aux horaires
    public function test_slot_with_animations_can_be_closed_and_renamed(): void
    {
        $slot = $this->slot();
        $this->animation($slot);

        $this->update($slot, ['draw_status' => 'closed', 'name' => 'Samedi'])->assertStatus(200);

        $slot->refresh();
        $this->assertSame('closed', $slot->draw_status);
        $this->assertSame('Samedi', $slot->name);
    }

    // 4 — Règle B : fin seule avant le début en base
    public function test_end_alone_before_stored_start_is_refused(): void
    {
        $slot = $this->slot();

        $this->update($slot, ['end_time' => '2020-01-01 00:00:00'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_time');

        $this->assertSame('2026-11-28 18:00:00', $slot->fresh()->end_time->format('Y-m-d H:i:s'));
    }

    public function test_start_alone_after_stored_end_is_refused(): void
    {
        $slot = $this->slot();

        $this->update($slot, ['start_time' => '2026-11-28 20:00:00'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_time');

        $this->assertSame('2026-11-28 14:00:00', $slot->fresh()->start_time->format('Y-m-d H:i:s'));
    }

    public function test_slot_outside_event_dates_is_refused(): void
    {
        $slot = $this->slot();

        $this->update($slot, ['start_time' => '2030-11-28 14:00:00', 'end_time' => '2030-11-28 18:00:00'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_time', 'end_time']);
    }

    public function test_slot_created_outside_event_dates_is_refused(): void
    {
        $this->postJson('/api/timeslot/timeslotadd', [
            'evenement_id' => $this->evenement->id,
            'name'         => 'Hors convention',
            'start_time'   => '2030-11-28 14:00:00',
            'end_time'     => '2030-11-28 18:00:00',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('time_slots', ['evenement_id' => $this->evenement->id, 'name' => 'Hors convention']);
    }

    public function test_slot_without_animations_can_be_moved_inside_event_dates(): void
    {
        $slot = $this->slot();

        $this->update($slot, ['start_time' => '2026-11-29 09:00:00', 'end_time' => '2026-11-29 13:00:00'])
            ->assertStatus(200);

        $this->assertSame('2026-11-29 09:00:00', $slot->fresh()->start_time->format('Y-m-d H:i:s'));
    }

    // 5 — Règle C
    public function test_slot_with_animations_cannot_be_moved(): void
    {
        $slot = $this->slot();
        $this->animation($slot);

        $this->update($slot, ['start_time' => '2026-11-28 09:00:00', 'end_time' => '2026-11-28 13:00:00'])
            ->assertStatus(422);

        $this->assertSame('2026-11-28 14:00:00', $slot->fresh()->start_time->format('Y-m-d H:i:s'));
    }

    public function test_slot_with_animations_accepts_its_current_times(): void
    {
        $slot = $this->slot();
        $this->animation($slot);

        $this->update($slot, ['name' => 'Samedi', 'start_time' => '2026-11-28 14:00', 'end_time' => '2026-11-28 18:00'])
            ->assertStatus(200);
    }

    // 6 — Règle D : réservé aux admins
    public function test_non_admin_cannot_update_a_slot(): void
    {
        $slot = $this->slot();
        Sanctum::actingAs($this->user('membre'));

        $this->update($slot, ['draw_status' => 'closed', 'name' => 'Autre'])->assertStatus(403);

        $this->assertSame('open', $slot->fresh()->draw_status);
    }

    private function animation(TimeSlot $slot): Animation
    {
        return Animation::create([
            'title'             => 'recette-a15 animation',
            'content'           => 'Test',
            'capacity'          => 3,
            'open_time'         => $slot->start_time,
            'closed_time'       => $slot->end_time,
            'user_id'           => $this->admin->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => DB::table('type_animations')->value('id'),
            'time_slot_id'      => $slot->id,
        ]);
    }
}
