<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\TimeSlot;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// B8 — Suppression d'un utilisateur (userDelete) :
// A. seul un compte sans aucune activité (ni vœu, ni animation dont il est l'auteur) est supprimé ;
// B. sinon refus 409 avec les nombres : il faut l'archiver ;
// C. au tirage, les vœux de comptes qui n'existent plus sont ignorés (orphelins déjà en base).
class UserDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->admin = $this->makeUser();
        Sanctum::actingAs($this->admin);
    }

    private function makeUser(): User
    {
        return User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Delete',
            'email'     => 'user-delete-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
    }

    // 'validate' n'est pas mass-assignable : on le pose directement.
    private function makeAnimation(User $author, ?TimeSlot $slot = null, int $capacity = 5): Animation
    {
        $animation = Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $author->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $slot?->id,
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => $capacity,
            'registration'      => true,
        ]);
        $animation->validate = true;
        $animation->save();

        return $animation;
    }

    private function wish(User $user, Animation $animation, string $registeredAt = null): Inscription
    {
        return Inscription::create([
            'user_id'       => $user->id,
            'animation_id'  => $animation->id,
            'weight'        => 1,
            'status'        => 'pending',
            'registered_at' => $registeredAt ?? now(),
        ]);
    }

    private function supprimer(User $user)
    {
        return $this->deleteJson('/api/user/userlist', ['id' => $user->id]);
    }

    // 1 — Règle A : compte créé par erreur ou en double
    public function test_account_without_activity_is_deleted(): void
    {
        $user = $this->makeUser();

        $this->supprimer($user)->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    // 2 — Règle B : un vœu suffit à bloquer, rien n'est supprimé
    public function test_account_with_wishes_is_refused_with_counts(): void
    {
        $user = $this->makeUser();
        $this->wish($user, $this->makeAnimation($this->admin));

        $this->supprimer($user)
            ->assertStatus(409)
            ->assertJson(['status' => 'false', 'counts' => ['voeux' => 1, 'animations' => 0]])
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertSame(1, Inscription::where('user_id', $user->id)->count());
    }

    // 3 — Règle B : auteur d'une animation
    public function test_animation_author_is_refused(): void
    {
        $user = $this->makeUser();
        $this->makeAnimation($user);

        $this->supprimer($user)
            ->assertStatus(409)
            ->assertJson(['counts' => ['voeux' => 0, 'animations' => 1]]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // 4 — Règle C : un vœu orphelin plus ancien ne prend pas la seule place au vrai joueur
    public function test_draw_ignores_wishes_of_deleted_accounts(): void
    {
        $slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
        ]);
        $animation = $this->makeAnimation($this->admin, $slot, 1);

        $fantome = $this->makeUser();
        $this->wish($fantome, $animation, '2026-10-01 08:00:00');
        // Compte supprimé sans passer par l'API : son vœu reste en base, comme les anciens orphelins
        DB::table('users')->where('id', $fantome->id)->delete();

        $joueur = $this->makeUser();
        $voeu = $this->wish($joueur, $animation, '2026-10-01 09:00:00');

        $this->postJson('/api/timeslot/timeslotdraw/' . $slot->id)->assertStatus(200);

        $this->assertSame('confirmed', $voeu->fresh()->status);
    }

    public function test_unknown_account_returns_404(): void
    {
        $this->deleteJson('/api/user/userlist', ['id' => 999999999])->assertStatus(404);
    }
}
