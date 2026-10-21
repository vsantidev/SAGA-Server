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

// Inscription, désinscription, statut d'inscription et modification du profil
// s'appliquent au compte connecté, quel que soit l'identifiant envoyé par l'écran.
// Un admin désinscrit un joueur depuis la liste des inscrits d'une animation.
class CompteConnecteTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $joueurA;
    private User $joueurB;
    private Animation $animation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->joueurA = $this->makeUser('A');
        $this->joueurB = $this->makeUser('B');

        $slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'recette-s2 créneau',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
        ]);
        $this->animation = Animation::create([
            'title'             => 'recette-s2 animation',
            'content'           => 'Contenu',
            'user_id'           => $this->joueurA->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $slot->id,
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => 5,
        ]);
        // Animation ouverte aux inscriptions (contrôles de createRegister)
        $this->animation->validate = 1;
        $this->animation->registration = 1;
        $this->animation->save();
    }

    private function makeUser(string $name, string $type = 'membre'): User
    {
        $user = User::create([
            'lastname'  => 'RECETTE-S2',
            'firstname' => $name,
            'email'     => 'recette-s2-' . strtolower($name) . '-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->save();

        return $user;
    }

    private function wish(User $user): Inscription
    {
        return Inscription::create([
            'user_id'       => $user->id,
            'animation_id'  => $this->animation->id,
            'weight'        => 1,
            'status'        => 'pending',
            'registered_at' => now(),
        ]);
    }

    private function hasWish(User $user): bool
    {
        return Inscription::where('user_id', $user->id)
            ->where('animation_id', $this->animation->id)
            ->exists();
    }

    // 1 — Inscription : le vœu est créé pour le compte connecté
    public function test_register_creates_the_wish_of_the_connected_account(): void
    {
        Sanctum::actingAs($this->joueurB);

        $this->postJson('/api/inscription/inscriptionadd/' . $this->animation->id, [
            'user_id' => $this->joueurA->id,
            'weight'  => 1,
        ])->assertStatus(201)->assertJson(['id' => $this->joueurB->id]);

        $this->assertTrue($this->hasWish($this->joueurB));
        $this->assertFalse($this->hasWish($this->joueurA));
    }

    // 2 — Désinscription : seul le vœu du compte connecté est supprimé
    public function test_unregister_deletes_only_the_wish_of_the_connected_account(): void
    {
        $this->wish($this->joueurA);
        $this->wish($this->joueurB);
        Sanctum::actingAs($this->joueurB);

        $this->deleteJson('/api/inscription/inscriptiondelete/' . $this->animation->id, [
            'user_id'      => $this->joueurA->id,
            'animation_id' => $this->animation->id,
        ])->assertOk();

        $this->assertTrue($this->hasWish($this->joueurA));
        $this->assertFalse($this->hasWish($this->joueurB));
    }

    // 3 — Désinscription par un admin depuis la liste des inscrits : inchangée
    public function test_admin_still_unregisters_another_player(): void
    {
        $this->wish($this->joueurA);
        Sanctum::actingAs($this->makeUser('Admin', 'admin'));

        $this->deleteJson('/api/inscription/inscriptiondelete/' . $this->animation->id, [
            'user_id'      => $this->joueurA->id,
            'animation_id' => $this->animation->id,
        ])->assertOk();

        $this->assertFalse($this->hasWish($this->joueurA));
    }

    // 4 — Statut d'inscription : celui du compte connecté
    public function test_registration_status_is_the_one_of_the_connected_account(): void
    {
        $this->wish($this->joueurA);
        Sanctum::actingAs($this->joueurB);

        $this->getJson('/api/inscription/inscriptionstatus?' . http_build_query([
            'user_id'      => $this->joueurA->id,
            'animation_id' => $this->animation->id,
            'time_slot_id' => $this->animation->time_slot_id,
        ]))->assertOk()
            ->assertJson(['inscription' => null, 'used_priorities' => []]);
    }

    // 5 — Modification du profil : celle du compte connecté (même corps que l'écran)
    public function test_profile_update_applies_to_the_connected_account(): void
    {
        $emailA = $this->joueurA->email;
        $emailB = 'recette-s2-b-new-' . uniqid() . '@test.local';
        Sanctum::actingAs($this->joueurB);

        $this->post('/api/user/userprofile/' . $this->joueurA->id, [
            'user' => json_encode([
                'id'           => $this->joueurA->id,
                'lastname'     => 'RECETTE-S2',
                'firstname'    => 'Modifié',
                'birthday'     => null,
                'phone'        => '0600000000',
                'email'        => $emailB,
                'presentation' => 'Présentation',
            ]),
        ])->assertOk()->assertJsonPath('user.id', $this->joueurB->id);

        $this->assertSame($emailA, $this->joueurA->fresh()->email);
        $this->assertSame('A', $this->joueurA->fresh()->firstname);
        $this->assertSame($emailB, $this->joueurB->fresh()->email);
        $this->assertSame('Modifié', $this->joueurB->fresh()->firstname);
    }
}
