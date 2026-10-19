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

// Règles de priorité contrôlées par le serveur : priorités 1 à 5, et chacune une seule fois
// par créneau et par joueur avant le calcul.
class InscriptionPrioritesTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private TimeSlot $slot;
    private User $joueur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->joueur = $this->makeUser('membre');
        Sanctum::actingAs($this->joueur);
        $this->slot = $this->makeSlot('2026-11-28 10:00:00');
    }

    // 'type' n'est pas mass-assignable : on le pose directement.
    private function makeUser(string $type): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Priorites',
            'email'     => 'inscr-prio-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->save();

        return $user;
    }

    private function makeSlot(string $start): TimeSlot
    {
        return TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => "Créneau $start",
            'start_time'   => $start,
            'end_time'     => date('Y-m-d H:i:s', strtotime($start) + 7200),
            'draw_status'  => 'open',
        ]);
    }

    // 'validate' n'est pas mass-assignable : on le pose directement.
    private function makeAnimation(TimeSlot $slot = null): Animation
    {
        $slot ??= $this->slot;
        $animation = Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->makeUser('animateur')->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $slot->id,
            'open_time'         => $slot->start_time,
            'closed_time'       => $slot->end_time,
            'time'              => 2,
            'capacity'          => 5,
            'registration'      => 1,
        ]);
        $animation->validate = true;
        $animation->save();

        return $animation->fresh();
    }

    private function register(Animation $animation, $weight)
    {
        return $this->postJson('/api/inscription/inscriptionadd/' . $animation->id, [
            'user_id' => $this->joueur->id,
            'weight'  => $weight,
        ]);
    }

    public function test_priority_outside_1_to_5_is_refused(): void
    {
        $animation = $this->makeAnimation();

        $this->register($animation, 6)->assertStatus(422);
        $this->register($animation, 0)->assertStatus(422);

        $this->assertSame(0, Inscription::where('animation_id', $animation->id)->count());
    }

    public function test_same_priority_twice_on_a_slot_is_refused(): void
    {
        $premier = $this->makeAnimation();
        $second = $this->makeAnimation();

        $this->register($premier, 1)->assertStatus(201);
        $this->register($second, 1)->assertStatus(409);

        $this->assertSame(0, Inscription::where('animation_id', $second->id)->count());
    }

    // Cinq priorités, donc cinq vœux au plus par créneau
    public function test_no_sixth_wish_on_a_slot(): void
    {
        foreach ([1, 2, 3, 4, 5] as $weight) {
            $this->register($this->makeAnimation(), $weight)->assertStatus(201);
        }

        $sixieme = $this->makeAnimation();
        foreach ([1, 2, 3, 4, 5] as $weight) {
            $this->register($sixieme, $weight)->assertStatus(409);
        }
    }

    public function test_same_priority_on_another_slot_is_accepted(): void
    {
        $autreCreneau = $this->makeSlot('2026-11-28 14:00:00');

        $this->register($this->makeAnimation(), 1)->assertStatus(201);
        $this->register($this->makeAnimation($autreCreneau), 1)->assertStatus(201);
    }

    // Supprimer son vœu libère la priorité (avec une nouvelle heure de dépôt, c'est voulu)
    public function test_priority_is_free_again_after_unsubscribing(): void
    {
        $premier = $this->makeAnimation();
        $second = $this->makeAnimation();
        $this->register($premier, 1)->assertStatus(201);

        $this->deleteJson('/api/inscription/inscriptiondelete/0', [
            'user_id'      => $this->joueur->id,
            'animation_id' => $premier->id,
        ])->assertStatus(200);

        $this->register($second, 1)->assertStatus(201);
    }

    // Après le calcul, l'écran envoie toujours la priorité 5 : elle n'a plus d'effet
    public function test_priority_is_not_checked_after_the_draw(): void
    {
        $refusee = $this->makeAnimation();
        Inscription::create([
            'user_id'       => $this->joueur->id,
            'animation_id'  => $refusee->id,
            'weight'        => 5,
            'status'        => 'rejected',
            'registered_at' => now(),
        ]);
        $this->slot->update(['draw_status' => 'drawn']);

        $animation = $this->makeAnimation();
        $this->register($animation, 5)->assertStatus(201);

        $this->assertSame('confirmed', Inscription::where('animation_id', $animation->id)->value('status'));
    }
}
