<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\Evenement_user;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Règle : le tirage des lots porte sur tous les participants de la convention,
// animateurs ou non, sauf les organisateurs, les admins et les archivés.
class EventLotDrawTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private array $eligibles = [];
    private array $exclus = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        Sanctum::actingAs($this->makeUser('admin', false, false));

        // On isole la convention : seuls les participants du test sont rattachés (annulé en fin de test)
        DB::table('evenement_users')->where('evenement_id', $this->evenement->id)->delete();

        $this->eligibles = [
            $this->makeUser('membre', false, true),
            $this->makeUser('animateur', false, true, true),
        ];
        $this->exclus = [
            $this->makeUser('archive', false, true),
            $this->makeUser('admin', false, true, true),
            $this->makeUser('membre', true, true, true),
        ];
    }

    // 'type' n'est pas mass-assignable : on le pose directement.
    private function makeUser(string $type, bool $isOrga, bool $linked, bool $masters = false): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Lot',
            'email'     => 'lot-draw-' . uniqid() . '@test.local',
            'password'  => 'secret',
            'is_orga'   => $isOrga,
        ]);
        $user->type = $type;
        $user->save();

        if ($linked) {
            $link = Evenement_user::create([
                'user_id'      => $user->id,
                'evenement_id' => $this->evenement->id,
            ]);
            $link->masters = $masters;
            $link->save();
        }

        return $user;
    }

    private function draw(int $nbLots)
    {
        return $this->postJson('/api/event/eventdraw', [
            'evenement_id' => $this->evenement->id,
            'nb_lots'      => $nbLots,
        ]);
    }

    private function hasWon(User $user): bool
    {
        return (bool) Evenement_user::where('user_id', $user->id)
            ->where('evenement_id', $this->evenement->id)
            ->value('winner_lot');
    }

    public function test_draw_only_picks_members_and_animators(): void
    {
        $this->draw(2)
            ->assertStatus(200)
            ->assertJson(['total_animateurs' => 2]);

        foreach ($this->eligibles as $user) {
            $this->assertTrue($this->hasWon($user));
        }
        foreach ($this->exclus as $user) {
            $this->assertFalse($this->hasWon($user));
        }
    }

    public function test_draw_refuses_more_lots_than_eligible_participants(): void
    {
        $this->draw(3)->assertStatus(422);
    }

    public function test_results_total_counts_only_eligible_participants(): void
    {
        $this->getJson('/api/event/eventresults?evenement_id=' . $this->evenement->id)
            ->assertStatus(200)
            ->assertJson(['total' => 2]);
    }
}
