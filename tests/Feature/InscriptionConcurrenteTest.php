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

// Inscription d'un joueur sur un créneau déjà calculé (createRegister), quand deux requêtes
// arrivent en même temps : l'une a passé les contrôles avant que l'autre ait écrit.
// A. une animation à 1 place n'a jamais 2 validés ;
// B. un joueur n'est jamais validé sur deux animations du même créneau ;
// C. de deux inscriptions simultanées, c'est la plus récente qui recule.
// La course est simulée en écrivant le vœu concurrent pendant la requête, juste avant
// (ou juste après) l'écriture du vœu du joueur.
class InscriptionConcurrenteTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private TimeSlot $timeSlot;
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->timeSlot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'TEST concurrence',
            'start_time'   => '2026-11-28 23:00:00',
            'end_time'     => '2026-11-29 00:00:00',
            'draw_status'  => 'drawn',
            'drawn_at'     => now(),
        ]);
        $this->player = $this->user('joueur');
        Sanctum::actingAs($this->player);
    }

    protected function tearDown(): void
    {
        Inscription::flushEventListeners();
        parent::tearDown();
    }

    private function user(string $name): User
    {
        return User::create([
            'lastname'  => 'TEST',
            'firstname' => $name,
            'email'     => "concurrence-$name-" . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
    }

    private function animation(int $capacity): Animation
    {
        $animation = Animation::create([
            'title'             => 'TEST concurrence',
            'content'           => 'TEST',
            'capacity'          => $capacity,
            'min_capacity'      => 1,
            'user_id'           => $this->user('animateur')->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::firstOrFail()->id,
            'time_slot_id'      => $this->timeSlot->id,
        ]);
        // Animation validée et ouverte aux inscriptions, comme celles que voit un joueur
        // (posé directement, comme 'validate' qui n'est pas mass-assignable)
        $animation->validate = true;
        $animation->registration = 1;
        $animation->save();

        return $animation;
    }

    private function confirmed(User $user, Animation $animation): void
    {
        // Écriture directe, sans repasser par les événements du modèle
        DB::table('inscriptions')->insert([
            'user_id'       => $user->id,
            'animation_id'  => $animation->id,
            'weight'        => 1,
            'status'        => 'confirmed',
            'registered_at' => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    // Le vœu concurrent est écrit après les contrôles, juste avant celui du joueur
    private function writtenJustBefore(callable $write): void
    {
        $done = false;
        Inscription::creating(function () use (&$done, $write) {
            if (!$done) {
                $done = true;
                $write();
            }
        });
    }

    private function register(Animation $animation)
    {
        return $this->postJson("/api/inscription/inscriptionadd/{$animation->id}", [
            'user_id' => $this->player->id,
            'weight'  => 1,
        ]);
    }

    // 1 — Règle A : un autre joueur prend la dernière place pendant la requête
    public function test_last_place_taken_meanwhile_is_refused(): void
    {
        $animation = $this->animation(1);
        $this->writtenJustBefore(fn() => $this->confirmed($this->user('rival'), $animation));

        $this->register($animation)
            ->assertStatus(409)
            ->assertJson(['message' => 'Plus de place sur cette animation.']);

        $this->assertSame(1, Inscription::where('animation_id', $animation->id)->where('status', 'confirmed')->count());
        $this->assertDatabaseMissing('inscriptions', ['user_id' => $this->player->id, 'animation_id' => $animation->id]);
    }

    // 2 — Règle B : le même joueur est validé ailleurs sur le créneau pendant la requête
    public function test_player_confirmed_meanwhile_on_the_same_slot_is_refused(): void
    {
        $wanted = $this->animation(5);
        $other  = $this->animation(5);
        $this->writtenJustBefore(fn() => $this->confirmed($this->player, $other));

        $this->register($wanted)
            ->assertStatus(409)
            ->assertJson(['message' => 'Vous êtes déjà confirmé sur une animation de ce créneau.']);

        $this->assertDatabaseMissing('inscriptions', ['user_id' => $this->player->id, 'animation_id' => $wanted->id]);
        $this->assertDatabaseHas('inscriptions', ['user_id' => $this->player->id, 'animation_id' => $other->id, 'status' => 'confirmed']);
    }

    // 3 — Règle C : le vœu concurrent arrive après celui du joueur : le joueur garde sa place
    public function test_older_registration_keeps_its_place(): void
    {
        $animation = $this->animation(1);
        $rival = $this->user('rival');
        Inscription::created(fn() => $this->confirmed($rival, $animation));

        $this->register($animation)->assertStatus(201);

        $this->assertDatabaseHas('inscriptions', ['user_id' => $this->player->id, 'animation_id' => $animation->id, 'status' => 'confirmed']);
    }

    // 4 — Sans course : inscription validée s'il reste une place
    public function test_registration_with_a_free_place_is_confirmed(): void
    {
        $animation = $this->animation(2);
        $this->confirmed($this->user('autre'), $animation);

        $this->register($animation)->assertStatus(201);

        $this->assertDatabaseHas('inscriptions', ['user_id' => $this->player->id, 'animation_id' => $animation->id, 'status' => 'confirmed']);
    }

    // 5 — Sans course : animation déjà pleine, comportement inchangé (vœu refusé)
    public function test_registration_on_a_full_animation_is_unchanged(): void
    {
        $animation = $this->animation(1);
        $this->confirmed($this->user('autre'), $animation);

        $this->register($animation)->assertStatus(201);

        $this->assertDatabaseHas('inscriptions', ['user_id' => $this->player->id, 'animation_id' => $animation->id, 'status' => 'rejected']);
    }

    // 6 — Avant le calcul : un vœu ne décide d'aucune place, rien ne change
    public function test_registration_before_the_draw_stays_pending(): void
    {
        $this->timeSlot->update(['draw_status' => 'open']);
        $animation = $this->animation(1);
        $this->writtenJustBefore(fn() => $this->confirmed($this->user('rival'), $animation));

        $this->register($animation)->assertStatus(201);

        $this->assertDatabaseHas('inscriptions', ['user_id' => $this->player->id, 'animation_id' => $animation->id, 'status' => 'pending']);
    }
}
