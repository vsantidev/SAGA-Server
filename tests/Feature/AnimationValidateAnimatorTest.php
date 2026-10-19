<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Evenement_user;
use App\Models\TimeSlot;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Validation d'une animation avec changement d'animateur : c'est l'animateur choisi par l'admin
// qui passe animateur (et master), pas l'auteur de la proposition.
class AnimationValidateAnimatorTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $admin;
    private User $proposeur;
    private User $animateur;
    private TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->admin     = $this->makeUser('admin');
        $this->proposeur = $this->makeUser('membre');
        $this->animateur = $this->makeUser('membre');
        Sanctum::actingAs($this->admin);

        $this->slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
        ]);
    }

    // 'type' n'est pas mass-assignable : on le pose directement.
    private function makeUser(string $type): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => ucfirst($type),
            'email'     => 'anim-validate-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->save();
        Evenement_user::create(['user_id' => $user->id, 'evenement_id' => $this->evenement->id]);

        return $user;
    }

    private function makeAnimation(User $author, bool $validate): Animation
    {
        $animation = Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $author->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $this->slot->id,
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => 5,
        ]);
        $animation->validate = $validate;
        $animation->save();

        return $animation->fresh();
    }

    // Reproduit l'appel du front : l'animation complète en JSON, l'animateur choisi dans 'adminAnimator'.
    private function update(Animation $animation, array $changes, ?User $adminAnimator = null)
    {
        $payload = array_merge($animation->fresh()->toArray(), $changes);
        $body = [
            'animation'  => json_encode($payload),
            'userUpdate' => json_encode(['id' => $this->admin->id]),
        ];
        if ($adminAnimator) {
            $body['adminAnimator'] = $adminAnimator->id;
        }

        return $this->postJson('/api/animation/animationShowEdit/' . $animation->id, $body);
    }

    private function masters(User $user): int
    {
        return (int) Evenement_user::where('user_id', $user->id)
            ->where('evenement_id', $this->evenement->id)
            ->value('masters');
    }

    // Valider et changer d'animateur en une seule sauvegarde
    public function test_new_animator_is_promoted_when_validating(): void
    {
        $animation = $this->makeAnimation($this->proposeur, false);

        $this->update($animation, ['validate' => 1], $this->animateur)
            ->assertStatus(200)
            ->assertJsonPath('authorLastname', $this->animateur->lastname)
            ->assertJsonPath('authorFirstname', $this->animateur->firstname);

        $this->assertEquals($this->animateur->id, $animation->fresh()->user_id);
        $this->assertSame('animateur', $this->animateur->fresh()->type);
        $this->assertSame(1, $this->masters($this->animateur));
        $this->assertSame('membre', $this->proposeur->fresh()->type);
        $this->assertSame(0, $this->masters($this->proposeur));
    }

    // Changer l'animateur d'une animation déjà validée
    public function test_new_animator_of_validated_animation_is_promoted(): void
    {
        $animation = $this->makeAnimation($this->proposeur, true);

        $this->update($animation, [], $this->animateur)->assertStatus(200);

        $this->assertSame('animateur', $this->animateur->fresh()->type);
        $this->assertSame(1, $this->masters($this->animateur));
    }

    // Validation sans changer d'animateur : l'auteur est promu, comme avant
    public function test_author_is_promoted_when_validating_without_change(): void
    {
        $animation = $this->makeAnimation($this->proposeur, false);

        $this->update($animation, ['validate' => 1])->assertStatus(200);

        $this->assertSame('animateur', $this->proposeur->fresh()->type);
        $this->assertSame(1, $this->masters($this->proposeur));
    }

    // Un admin choisi comme animateur reste admin
    public function test_admin_animator_stays_admin(): void
    {
        $autreAdmin = $this->makeUser('admin');
        $animation = $this->makeAnimation($this->proposeur, false);

        $this->update($animation, ['validate' => 1], $autreAdmin)->assertStatus(200);

        $this->assertSame('admin', $autreAdmin->fresh()->type);
        $this->assertSame('membre', $this->proposeur->fresh()->type);
    }
}
