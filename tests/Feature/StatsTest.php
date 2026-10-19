<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Page Statistiques (stats/animations et stats/capacity-by-tranche) :
// A. seules les animations validées comptent ;
// B. une animation entre minuit et 8 h compte dans la tranche « Nuit » ;
// C. le nom de la convention est son titre ;
// D. sans animation validée, la réponse est un message (200), pas une erreur.
class StatsTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $auteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->auteur = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Stats',
            'email'     => 'stats-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($this->auteur);
    }

    // 'validate' n'est pas mass-assignable : on le pose directement.
    private function animation(string $open, int $capacity, bool $validate): Animation
    {
        $animation = Animation::create([
            'title'             => 'Stats ' . $open,
            'content'           => 'Contenu',
            'user_id'           => $this->auteur->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'open_time'         => $open,
            'closed_time'       => date('Y-m-d H:i:s', strtotime($open) + 7200),
            'time'              => 2,
            'capacity'          => $capacity,
        ]);
        $animation->validate = $validate;
        $animation->save();

        return $animation;
    }

    private function tranches(): array
    {
        return $this->getJson('/api/stats/capacity-by-tranche')->assertStatus(200)->json();
    }

    // A
    public function test_non_validated_animation_is_not_counted(): void
    {
        $avant = $this->tranches()['tranches_par_jour']['2030-01-05']['Soir']['capacity'] ?? 0;

        $refusee = $this->animation('2030-01-05 20:00:00', 20, false);

        $this->assertSame($avant, $this->tranches()['tranches_par_jour']['2030-01-05']['Soir']['capacity'] ?? 0);
        $this->assertNotContains($refusee->id, array_column($this->getJson('/api/stats/animations')->json(), 'id'));
    }

    // B : capacité + 1 (l'animateur), comme pour les autres tranches
    public function test_night_animation_counts_in_night_tranche(): void
    {
        $this->animation('2030-01-06 00:30:00', 6, true);

        $this->assertSame(7, $this->tranches()['tranches_par_jour']['2030-01-06']['Nuit']['capacity']);
    }

    // C
    public function test_event_name_is_its_title(): void
    {
        $this->assertSame($this->evenement->title, $this->tranches()['evenement_nom']);
    }

    // D
    public function test_no_validated_animation_gives_a_message(): void
    {
        DB::table('animations')->where('evenement_id', $this->evenement->id)->update(['validate' => 0]);

        $this->getJson('/api/stats/animations')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Aucune animation validée pour la convention active.');
    }
}
