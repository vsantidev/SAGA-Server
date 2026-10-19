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

// Vœux et calcul : seules les animations validées, ouvertes aux inscriptions, avec un créneau
// et de la convention active reçoivent des vœux et entrent dans le calcul.
class InscriptionEligibiliteTest extends TestCase
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

        $this->slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
            'draw_status'  => 'open',
        ]);
    }

    // 'type' n'est pas mass-assignable : on le pose directement.
    private function makeUser(string $type): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Eligibilite',
            'email'     => 'inscr-elig-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->save();

        return $user;
    }

    // 'validate' n'est pas mass-assignable : on le pose directement.
    private function makeAnimation(array $attributes = [], bool $validate = true): Animation
    {
        $animation = Animation::create(array_merge([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->makeUser('animateur')->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $this->slot->id,
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => 5,
            'registration'      => 1,
        ], $attributes));
        $animation->validate = $validate;
        $animation->save();

        return $animation->fresh();
    }

    private function register(Animation $animation, User $user = null, int $weight = 1)
    {
        $user ??= $this->joueur;

        return $this->postJson('/api/inscription/inscriptionadd/' . $animation->id, [
            'user_id'      => $user->id,
            'weight'       => $weight,
            'time_slot_id' => $animation->time_slot_id,
        ]);
    }

    private function wish(Animation $animation, User $user, int $weight): Inscription
    {
        return Inscription::create([
            'user_id'       => $user->id,
            'animation_id'  => $animation->id,
            'weight'        => $weight,
            'status'        => 'pending',
            'registered_at' => now(),
        ]);
    }

    public function test_wish_on_validated_animation_is_accepted(): void
    {
        $this->register($this->makeAnimation())->assertStatus(201);
    }

    public function test_wish_on_non_validated_animation_is_refused(): void
    {
        $animation = $this->makeAnimation([], false);

        $this->register($animation)->assertStatus(403);

        $this->assertSame(0, Inscription::where('animation_id', $animation->id)->count());
    }

    public function test_wish_on_animation_without_registration_is_refused(): void
    {
        $animation = $this->makeAnimation(['registration' => 0]);

        $this->register($animation)->assertStatus(403);

        $this->assertSame(0, Inscription::where('animation_id', $animation->id)->count());
    }

    public function test_wish_on_animation_without_time_slot_is_refused_clearly(): void
    {
        $animation = $this->makeAnimation(['time_slot_id' => null], false);
        $animation->validate = true;   // cas atteignable par l'API seulement
        $animation->save();

        $this->register($animation)->assertStatus(422);
    }

    public function test_wish_on_animation_of_another_convention_is_refused(): void
    {
        $ancienne = Evenement::where('actif', 0)->firstOrFail();
        $animation = $this->makeAnimation(['evenement_id' => $ancienne->id]);

        $this->register($animation)->assertStatus(403);
    }

    // Calcul : un vœu priorité 1 sur une animation non validée ne prend pas la place
    // du vrai vœu du joueur (priorité 2 sur une animation validée)
    public function test_draw_ignores_wishes_on_non_validated_animation(): void
    {
        $nonValidee = $this->makeAnimation([], false);
        $validee = $this->makeAnimation();
        $p1 = $this->wish($nonValidee, $this->joueur, 1);
        $p2 = $this->wish($validee, $this->joueur, 2);

        $this->postJson('/api/timeslot/timeslotdraw/' . $this->slot->id)->assertStatus(200);

        $this->assertSame('confirmed', $p2->fresh()->status);
        $this->assertSame('pending', $p1->fresh()->status);
    }

    public function test_draw_ignores_wishes_on_animation_without_registration(): void
    {
        $sansInscription = $this->makeAnimation(['registration' => 0]);
        $wish = $this->wish($sansInscription, $this->makeUser('membre'), 1);

        $this->postJson('/api/timeslot/timeslotdraw/' . $this->slot->id)->assertStatus(200);

        $this->assertSame('pending', $wish->fresh()->status);
    }

    // Après le calcul, une inscription suit toujours le chemin « après calcul »
    public function test_wish_after_draw_is_confirmed_when_places_are_left(): void
    {
        $animation = $this->makeAnimation();
        $this->postJson('/api/timeslot/timeslotdraw/' . $this->slot->id)->assertStatus(200);

        $this->register($animation)->assertStatus(201);

        $this->assertSame('confirmed', Inscription::where('animation_id', $animation->id)->value('status'));
    }

    public function test_second_draw_is_still_refused(): void
    {
        $this->postJson('/api/timeslot/timeslotdraw/' . $this->slot->id)->assertStatus(200);
        $this->postJson('/api/timeslot/timeslotdraw/' . $this->slot->id)->assertStatus(403);
    }
}
