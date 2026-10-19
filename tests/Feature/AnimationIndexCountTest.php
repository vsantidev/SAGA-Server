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

// Compteur nb_inscrits de l'accueil (animationIndex), qui décide aussi de « Complet » :
// après le calcul, seuls les validés comptent ; avant, les vœux en attente ;
// les vœux de comptes supprimés ne comptent jamais.
class AnimationIndexCountTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->viewer = $this->makeUser();
        Sanctum::actingAs($this->viewer);
    }

    private function makeUser(): User
    {
        return User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Compteur',
            'email'     => 'anim-count-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
    }

    private function makeAnimation(string $drawStatus): Animation
    {
        $slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
            'draw_status'  => $drawStatus,
        ]);
        $animation = Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->viewer->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $slot->id,
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => 6,
        ]);
        // 'validate' n'est pas mass-assignable : on le pose directement.
        $animation->validate = true;
        $animation->save();

        return $animation;
    }

    private function addInscriptions(Animation $animation, string $status, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Inscription::create([
                'user_id'       => $this->makeUser()->id,
                'animation_id'  => $animation->id,
                'weight'        => 1,
                'status'        => $status,
                'registered_at' => now(),
            ]);
        }
    }

    private function nbInscrits(Animation $animation): int
    {
        $liste = $this->getJson('/api/animation/animationIndex?param1=' . $this->viewer->id)
            ->assertStatus(200)
            ->json('listeAnimation');

        return (int) collect($liste)->firstWhere('id', $animation->id)['nb_inscrits'];
    }

    // Après le calcul : 4 validés et 4 placés ailleurs sur 6 places → 4, pas « Complet »
    public function test_after_draw_only_confirmed_are_counted(): void
    {
        $animation = $this->makeAnimation('drawn');
        $this->addInscriptions($animation, 'confirmed', 4);
        $this->addInscriptions($animation, 'rejected', 4);
        $this->addInscriptions($animation, 'cascade_cancelled', 1);

        $this->assertSame(4, $this->nbInscrits($animation));
    }

    // Avant le calcul : toutes les demandes en attente comptent (« À vos prios ! »)
    public function test_before_draw_pending_wishes_are_counted(): void
    {
        $animation = $this->makeAnimation('open');
        $this->addInscriptions($animation, 'pending', 8);

        $this->assertSame(8, $this->nbInscrits($animation));
    }

    // Vœu d'un compte supprimé : ne compte plus
    public function test_wish_of_deleted_account_is_not_counted(): void
    {
        $animation = $this->makeAnimation('drawn');
        $this->addInscriptions($animation, 'confirmed', 2);
        $orphelin = $this->makeUser();
        Inscription::create([
            'user_id'       => $orphelin->id,
            'animation_id'  => $animation->id,
            'weight'        => 1,
            'status'        => 'confirmed',
            'registered_at' => now(),
        ]);
        $orphelin->delete();

        $this->assertSame(2, $this->nbInscrits($animation));
    }
}
