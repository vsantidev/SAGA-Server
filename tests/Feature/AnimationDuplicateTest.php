<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Règle : une copie va toujours dans la convention active, sans créneau,
// non validée, en gardant la salle (le conflit est vérifié à l'affectation du créneau).
class AnimationDuplicateTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        Sanctum::actingAs(User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Duplicate',
            'email'     => 'anim-duplicate-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]));
    }

    private function duplicate(Animation $animation)
    {
        return $this->postJson('/api/animation/animationList', ['id' => $animation->id]);
    }

    private function assertCopyInActiveEvent(Animation $original, int $copyId): void
    {
        $copy = Animation::findOrFail($copyId);

        $this->assertNotSame($original->id, $copy->id);
        $this->assertEquals($this->evenement->id, $copy->evenement_id);
        $this->assertNull($copy->time_slot_id);
        $this->assertEquals($original->room_id, $copy->room_id);
        $this->assertEquals(0, $copy->validate);
        $this->assertSame($original->title, $copy->title);
        $this->assertEquals($original->user_id, $copy->user_id);
    }

    public function test_duplicate_from_previous_event_goes_to_active_event(): void
    {
        $original = Animation::where('evenement_id', '!=', $this->evenement->id)
            ->whereNotNull('room_id')
            ->firstOrFail();

        $response = $this->duplicate($original)->assertStatus(200);

        $this->assertCopyInActiveEvent($original, $response->json('idNewAnimation'));
    }

    public function test_duplicate_within_active_event_clears_time_slot_and_keeps_room(): void
    {
        $original = Animation::where('evenement_id', $this->evenement->id)
            ->whereNotNull('time_slot_id')
            ->whereNotNull('room_id')
            ->firstOrFail();

        $response = $this->duplicate($original)->assertStatus(200);

        $this->assertCopyInActiveEvent($original, $response->json('idNewAnimation'));
    }

    public function test_duplicate_without_active_event_returns_422(): void
    {
        $original = Animation::firstOrFail();
        Evenement::where('actif', 1)->update(['actif' => 0]);
        $countBefore = Animation::count();

        $this->duplicate($original)->assertStatus(422);

        $this->assertSame($countBefore, Animation::count());
    }

    public function test_duplicate_does_not_copy_registrations(): void
    {
        $original = Animation::has('inscriptions')->first() ?? Animation::firstOrFail();

        $response = $this->duplicate($original)->assertStatus(200);

        $this->assertSame(0, Inscription::where('animation_id', $response->json('idNewAnimation'))->count());
    }
}
