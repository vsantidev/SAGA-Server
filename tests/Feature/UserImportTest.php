<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\Evenement_user;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserImportTest extends TestCase
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
    private function makeUser(string $type, string $picture = 'images/users/img_default_esprit.png'): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Import',
            'email'     => 'user-import-' . uniqid() . '@test.local',
            'password'  => 'secret',
            'picture'   => $picture,
        ]);
        $user->type = $type;
        $user->save();

        return $user;
    }

    private function import(User $user)
    {
        return $this->postJson('/api/user/userimport', [[
            'email'     => $user->email,
            'lastname'  => $user->lastname,
            'firstname' => $user->firstname,
        ]]);
    }

    public function test_archived_member_becomes_member_and_is_linked_to_active_event(): void
    {
        $user = $this->makeUser('archive');

        $this->import($user)->assertStatus(200);

        $this->assertSame('membre', $user->fresh()->type);
        $this->assertDatabaseHas('evenement_users', [
            'user_id'      => $user->id,
            'evenement_id' => $this->evenement->id,
        ]);
    }

    public function test_animator_becomes_member(): void
    {
        $user = $this->makeUser('animateur');

        $this->import($user)->assertStatus(200);

        $this->assertSame('membre', $user->fresh()->type);
    }

    public function test_admin_stays_admin(): void
    {
        $user = $this->makeUser('admin');

        $this->import($user)->assertStatus(200);

        $this->assertSame('admin', $user->fresh()->type);
    }

    // Une réimportation pendant la convention ne crée pas de doublon et ne retire pas le lot gagné.
    public function test_existing_link_with_lot_is_kept_without_duplicate(): void
    {
        $user = $this->makeUser('membre');
        Evenement_user::create([
            'user_id'        => $user->id,
            'evenement_id'   => $this->evenement->id,
            'winner_lot'     => true,
            'winner_lot_pos' => 2,
        ]);

        $this->import($user)->assertStatus(200);

        $links = Evenement_user::where('user_id', $user->id)
            ->where('evenement_id', $this->evenement->id)
            ->get();
        $this->assertCount(1, $links);
        $this->assertEquals(1, $links->first()->winner_lot);
        $this->assertEquals(2, $links->first()->winner_lot_pos);
    }

    public function test_old_default_avatar_is_replaced(): void
    {
        $user = $this->makeUser('archive', 'images/users/img_default_viking.png');

        $this->import($user)->assertStatus(200);

        $this->assertSame('images/users/img_default_esprit.png', $user->fresh()->picture);
    }
}
