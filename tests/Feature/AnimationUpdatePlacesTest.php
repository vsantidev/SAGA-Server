<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Modification d'une animation (animationUpdate) : les nombres de places suivent les mêmes règles
// qu'à la création. Places maxi : entier d'au moins 1 ; places mini : facultatif, entier positif,
// pas plus que le maxi. Un refus est un 422 avec un message, jamais une erreur 500.
class AnimationUpdatePlacesTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->author = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Places',
            'email'     => 'anim-places-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($this->author);
    }

    private function makeAnimation(): Animation
    {
        return Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->author->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => 6,
            'min_capacity'      => 3,
        ])->fresh();
    }

    // Reproduit l'appel du front : l'animation complète en JSON dans le champ 'animation'.
    private function update(Animation $animation, array $changes)
    {
        $payload = array_merge($animation->fresh()->toArray(), $changes);

        return $this->postJson('/api/animation/animationShowEdit/' . $animation->id, [
            'animation'  => json_encode($payload),
            'userUpdate' => json_encode(['id' => $this->author->id]),
        ]);
    }

    public function test_empty_max_places_is_refused_without_error_500(): void
    {
        $animation = $this->makeAnimation();

        $this->update($animation, ['capacity' => ''])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Merci de renseigner le nombre de joueurs max (au moins 1)')
            // un seul message : le mini n'est pas comparé à un maxi vide
            ->assertJsonMissingPath('errors.min_capacity');

        $this->assertSame(6, $animation->fresh()->capacity);
    }

    public function test_zero_or_negative_max_places_is_refused(): void
    {
        $animation = $this->makeAnimation();

        $this->update($animation, ['capacity' => 0])->assertStatus(422);
        $this->update($animation, ['capacity' => -3])->assertStatus(422);

        $this->assertSame(6, $animation->fresh()->capacity);
    }

    public function test_min_places_above_max_is_refused(): void
    {
        $animation = $this->makeAnimation();

        $this->update($animation, ['min_capacity' => 8])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Le nombre de places mini ne peut pas dépasser le nombre de places maxi');

        $this->assertSame(3, $animation->fresh()->min_capacity);
    }

    // Vider les places mini retire le minimum (avant : erreur 500)
    public function test_emptying_min_places_removes_the_minimum(): void
    {
        $animation = $this->makeAnimation();

        $this->update($animation, ['min_capacity' => ''])->assertStatus(200);

        $this->assertNull($animation->fresh()->min_capacity);
    }

    public function test_valid_places_are_saved(): void
    {
        $animation = $this->makeAnimation();

        $this->update($animation, ['capacity' => 10, 'min_capacity' => 4])->assertStatus(200);

        $this->assertSame(10, $animation->fresh()->capacity);
        $this->assertSame(4, $animation->fresh()->min_capacity);
    }
}
