<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\Room;
use App\Models\TimeSlot;
use App\Models\Type_animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Nom de l'animateur affiché : « Élodie LEFÈVRE », accents compris, et le même sur l'accueil,
// la liste admin des animations, le calendrier et « Mes inscriptions » (joueur et animateur).
class AuthorNameTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;
    private User $animateur;
    private User $joueur;
    private Animation $animation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $this->animateur = User::create([
            'lastname'  => 'Lefèvre',
            'firstname' => 'élodie',
            'email'     => 'nom-anim-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $this->joueur = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Joueur',
            'email'     => 'nom-joueur-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $this->joueur->type = 'admin';
        $this->joueur->save();

        $slot = TimeSlot::create([
            'evenement_id' => $this->evenement->id,
            'name'         => 'Créneau de test',
            'start_time'   => '2026-11-28 10:00:00',
            'end_time'     => '2026-11-28 12:00:00',
            'draw_status'  => 'open',
        ]);
        $this->animation = Animation::create([
            'title'             => 'Animation de test',
            'content'           => 'Contenu',
            'user_id'           => $this->animateur->id,
            'evenement_id'      => $this->evenement->id,
            'type_animation_id' => Type_animation::value('id'),
            'time_slot_id'      => $slot->id,
            // Le calendrier ne montre que les animations d'une salle active
            'room_id'           => Room::where('active', 1)->value('id'),
            'open_time'         => '2026-11-28 10:00:00',
            'closed_time'       => '2026-11-28 12:00:00',
            'time'              => 2,
            'capacity'          => 5,
            'registration'      => 1,
        ]);
        // 'validate' n'est pas mass-assignable : on le pose directement.
        $this->animation->validate = true;
        $this->animation->save();

        Inscription::create([
            'user_id'       => $this->joueur->id,
            'animation_id'  => $this->animation->id,
            'weight'        => 1,
            'status'        => 'pending',
            'registered_at' => now(),
        ]);
        Sanctum::actingAs($this->joueur);
    }

    private function fromList(array $list, string $field): ?string
    {
        foreach ($list as $item) {
            if (($item['id'] ?? null) === $this->animation->id) {
                return $item[$field];
            }
        }
        $this->fail('animation absente de la liste');
    }

    private function fromInscriptions(User $user): ?string
    {
        $items = $this->getJson('/api/inscription/userInscriptions?user_id=' . $user->id)
            ->assertStatus(200)
            ->json('inscriptions');
        foreach ($items as $item) {
            if ($item['animation']['id'] === $this->animation->id) {
                return $item['animation']['animator'];
            }
        }
        $this->fail('animation absente de « Mes inscriptions »');
    }

    public function test_author_name_on_home_page(): void
    {
        $list = $this->getJson('/api/animation/animationIndex?param1=' . $this->joueur->id)->json('listeAnimation');
        $this->assertSame('Élodie LEFÈVRE', $this->fromList($list, 'author_name'));
    }

    public function test_author_name_on_admin_list(): void
    {
        $list = $this->getJson('/api/animation/animationList')->json('listeAnimation');
        $this->assertSame('Élodie LEFÈVRE', $this->fromList($list, 'author_name'));
    }

    public function test_author_name_on_calendar(): void
    {
        $list = $this->getJson('/api/animation/animationListValidate?param1=' . $this->joueur->id)->json('listeAnimation');
        $this->assertSame('Élodie LEFÈVRE', $this->fromList($list, 'author_name'));
    }

    public function test_animator_name_in_player_registrations(): void
    {
        $this->assertSame('Élodie LEFÈVRE', $this->fromInscriptions($this->joueur));
    }

    public function test_animator_name_in_animator_registrations(): void
    {
        Sanctum::actingAs($this->animateur);
        $this->assertSame('Élodie LEFÈVRE', $this->fromInscriptions($this->animateur));
    }

    // Prénom composé : majuscule à chaque partie (après un tiret ou une espace), à l'accueil comme ailleurs
    public function test_compound_first_name_gets_a_capital_on_each_part(): void
    {
        $this->animateur->firstname = 'jean-christophe';
        $this->animateur->save();

        $home = $this->getJson('/api/animation/animationIndex?param1=' . $this->joueur->id)->json('listeAnimation');
        $this->assertSame('Jean-Christophe LEFÈVRE', $this->fromList($home, 'author_name'));

        $calendar = $this->getJson('/api/animation/animationListValidate?param1=' . $this->joueur->id)->json('listeAnimation');
        $this->assertSame('Jean-Christophe LEFÈVRE', $this->fromList($calendar, 'author_name'));

        $this->animateur->firstname = 'MARIE laure';
        $this->animateur->save();
        $this->assertSame('Marie Laure LEFÈVRE', $this->fromInscriptions($this->joueur));
    }
}
