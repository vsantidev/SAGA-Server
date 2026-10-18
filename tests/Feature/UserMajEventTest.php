<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\Evenement_user;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserMajEventTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        Sanctum::actingAs($this->makeUser('admin'));
    }

    // 'type' n'est pas mass-assignable : on le pose directement.
    private function makeUser(string $type): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Maj',
            'email'     => 'user-maj-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->save();

        return $user;
    }

    private function linksToActiveEvent(User $user): int
    {
        return Evenement_user::where('user_id', $user->id)
            ->where('evenement_id', $this->evenement->id)
            ->count();
    }

    // Un participant d'une édition précédente est rattaché à la convention active.
    public function test_member_of_previous_event_is_linked_to_active_event(): void
    {
        $user = $this->makeUser('membre');
        $ancienne = Evenement::where('actif', 0)->firstOrFail();
        Evenement_user::create(['user_id' => $user->id, 'evenement_id' => $ancienne->id]);

        $this->postJson('/api/user/usermaj')->assertStatus(200);

        $this->assertSame(1, $this->linksToActiveEvent($user));
    }

    public function test_archived_user_is_not_linked(): void
    {
        $user = $this->makeUser('archive');

        $this->postJson('/api/user/usermaj')->assertStatus(200);

        $this->assertSame(0, $this->linksToActiveEvent($user));
    }

    public function test_already_linked_user_gets_no_duplicate(): void
    {
        $user = $this->makeUser('membre');
        Evenement_user::create(['user_id' => $user->id, 'evenement_id' => $this->evenement->id]);

        $this->postJson('/api/user/usermaj')->assertStatus(200);

        $this->assertSame(1, $this->linksToActiveEvent($user));
    }
}
