<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\TimeSlot;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Le nombre de places mini est facultatif (Trello #188) :
// vide ou absent, l'animation est créée ; rempli, c'est un entier entre 0 et le nombre de places maxi.
class AnimationCreateMinCapacityTest extends TestCase
{
    use DatabaseTransactions;

    private User $author;
    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->author = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Create',
            'email'     => 'anim-create-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($this->author);

        $this->slot = TimeSlot::create([
            'evenement_id' => $evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
        ]);
    }

    // Reproduit l'envoi du formulaire « Demande création » (multipart, sans image).
    private function create(array $changes)
    {
        $payload = array_merge([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'capacity'          => 5,
            'user_id'           => $this->author->id,
            'time_slot_id'      => $this->slot->id,
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'registration'      => '1',
            'multiple'          => '0',
            'fight'             => 1,
            'reflection'        => 1,
            'roleplay'          => 1,
            'type_animation_id' => Type_animation::value('id'),
        ], $changes);

        return $this->post('/api/animation/animationCreate', $payload, ['Accept' => 'application/json']);
    }

    public function test_without_min_capacity_the_animation_is_created(): void
    {
        $response = $this->create([]);

        $response->assertStatus(201);
        $this->assertNull($response->json('animation.min_capacity'));
    }

    public function test_empty_min_capacity_is_accepted(): void
    {
        $this->create(['min_capacity' => ''])->assertStatus(201);
    }

    public function test_min_capacity_within_capacity_is_saved(): void
    {
        $response = $this->create(['min_capacity' => 3]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('animations', ['id' => $response->json('animation.id'), 'min_capacity' => 3]);
    }

    // Ce que l'ancien écran envoyait quand le champ n'était pas touché : refus lisible, plus d'erreur 500.
    public function test_text_min_capacity_is_refused(): void
    {
        $this->create(['min_capacity' => 'undefined'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('min_capacity');
    }

    public function test_min_capacity_above_capacity_is_refused(): void
    {
        $this->create(['min_capacity' => 50])
            ->assertStatus(422)
            ->assertJsonValidationErrors('min_capacity');
    }

    public function test_negative_min_capacity_is_refused(): void
    {
        $this->create(['min_capacity' => -3])
            ->assertStatus(422)
            ->assertJsonValidationErrors('min_capacity');
    }
}
