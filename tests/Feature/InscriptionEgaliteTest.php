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

// Égalités : l'heure de dépôt est à la seconde. Entre vœux de même priorité déposés dans la même
// seconde, le premier arrivé (plus petit identifiant) passe devant, au calcul comme sur la fiche.
class InscriptionEgaliteTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        Sanctum::actingAs($this->makeUser('admin'));

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
            'firstname' => 'Egalite',
            'email'     => 'inscr-egalite-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->save();

        return $user;
    }

    // 'validate' n'est pas mass-assignable : on le pose directement.
    private function makeAnimation(int $capacity): Animation
    {
        $animation = Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->makeUser('animateur')->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $this->slot->id,
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => $capacity,
            'registration'      => 1,
        ]);
        $animation->validate = 1;
        $animation->save();

        return $animation->fresh();
    }

    // Vœux de priorité 1, tous déposés à la même seconde, dans l'ordre de création
    private function wishesAtSameSecond(Animation $animation, int $count): array
    {
        $wishes = [];
        for ($i = 0; $i < $count; $i++) {
            $wishes[] = Inscription::create([
                'user_id'       => $this->makeUser('membre')->id,
                'animation_id'  => $animation->id,
                'weight'        => 1,
                'status'        => 'pending',
                'registered_at' => '2026-11-20 09:00:00',
            ]);
        }

        return $wishes;
    }

    // Calcul : 2 places pour 8 vœux à égalité, ce sont les 2 premiers arrivés qui sont validés
    public function test_draw_keeps_arrival_order_between_ties(): void
    {
        $animation = $this->makeAnimation(2);
        $wishes = $this->wishesAtSameSecond($animation, 8);

        $this->postJson('/api/timeslot/timeslotdraw/' . $this->slot->id)->assertStatus(200);

        $statuses = array_map(fn ($w) => $w->fresh()->status, $wishes);
        $this->assertSame(
            ['confirmed', 'confirmed', 'rejected', 'rejected', 'rejected', 'rejected', 'rejected', 'rejected'],
            $statuses
        );
    }

    // Calcul à l'échelle d'une ouverture : 300 joueurs, 1 à 5 priorités chacun sur 15 animations
    // de 3 places, déposées en quelques secondes (beaucoup d'égalités). Le résultat doit suivre
    // la règle : priorité, puis heure de dépôt, puis ordre d'arrivée ; une place par joueur.
    public function test_draw_keeps_arrival_order_at_opening_scale(): void
    {
        $animations = [];
        for ($a = 0; $a < 15; $a++) {
            $animations[] = $this->makeAnimation(3)->id;
        }

        $now = now();
        $users = [];
        for ($i = 0; $i < 300; $i++) {
            $users[] = [
                'lastname'   => 'TEST',
                'firstname'  => 'Egalite',
                'email'      => 'inscr-egalite-' . uniqid() . "-$i@test.local",
                'password'   => 'x',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        User::insert($users);
        $userIds = User::where('email', 'like', 'inscr-egalite-%')->orderByDesc('id')->limit(300)->pluck('id')->all();

        // Dépôts mélangés : chaque joueur donne ses priorités sur des animations différentes
        mt_srand(7);
        $rows = [];
        foreach ($userIds as $userId) {
            $choices = $animations;
            shuffle($choices);
            for ($weight = 1; $weight <= mt_rand(1, 5); $weight++) {
                $rows[] = [
                    'user_id'       => $userId,
                    'animation_id'  => $choices[$weight - 1],
                    'weight'        => $weight,
                    'status'        => 'pending',
                    'registered_at' => '2026-11-20 09:00:0' . mt_rand(0, 3),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }
        shuffle($rows);
        Inscription::insert($rows);

        $this->postJson('/api/timeslot/timeslotdraw/' . $this->slot->id)->assertStatus(200);

        // Calcul de référence, d'après la règle
        $wishes = Inscription::whereIn('animation_id', $animations)->get()
            ->sortBy([['weight', 'asc'], ['registered_at', 'asc'], ['id', 'asc']]);
        $places = array_fill_keys($animations, 3);
        $placed = [];
        $expected = [];
        foreach ($wishes as $wish) {
            if (isset($placed[$wish->user_id])) {
                $expected[$wish->id] = 'cascade_cancelled';
            } elseif ($places[$wish->animation_id] > 0) {
                $places[$wish->animation_id]--;
                $placed[$wish->user_id] = true;
                $expected[$wish->id] = 'confirmed';
            } else {
                $expected[$wish->id] = 'rejected';
            }
        }

        $actual = $wishes->pluck('status', 'id')->all();
        $gaps = array_keys(array_diff_assoc($expected, $actual));
        $this->assertSame([], $gaps, count($gaps) . ' vœux ne suivent pas la règle');
    }

    // Fiche : la liste des inscrits suit le même ordre que le calcul
    public function test_animation_page_lists_ties_in_arrival_order(): void
    {
        $animation = $this->makeAnimation(2);
        $wishes = $this->wishesAtSameSecond($animation, 8);

        $listed = $this->getJson('/api/animation/animationShow/' . $animation->id)
            ->assertStatus(200)
            ->json('listInscrits.*.id');

        $this->assertSame(array_map(fn ($w) => $w->user_id, $wishes), $listed);
    }
}
