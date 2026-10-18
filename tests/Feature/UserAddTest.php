<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Ajout d'un utilisateur par un admin (useradd) :
// A. le compte est créé et rattaché à la convention active, sans jeton de session ;
// B. un e-mail déjà connu est refusé avec un message clair et le compte existant ;
// C. sans convention active, rien n'est créé.
class UserAddTest extends TestCase
{
    use DatabaseTransactions;

    private Evenement $evenement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evenement = Evenement::where('actif', 1)->firstOrFail();
        $admin = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Admin',
            'email'     => 'useradd-admin-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($admin);
    }

    private function add(array $data)
    {
        return $this->postJson('/api/user/useradd', array_merge([
            'lastname'  => 'NOUVEAU',
            'firstname' => 'Compte',
        ], $data));
    }

    // 1 — Règle A
    public function test_new_user_is_created_and_attached_to_the_active_event(): void
    {
        $email = 'useradd-' . uniqid() . '@test.local';

        $response = $this->add(['email' => $email]);

        $response->assertStatus(201);
        $response->assertJsonMissingPath('token');
        $user = User::where('email', $email)->firstOrFail();
        $this->assertDatabaseHas('evenement_users', ['user_id' => $user->id, 'evenement_id' => $this->evenement->id]);
        $this->assertSame(0, $user->tokens()->count());
    }

    // 2 — Même normalisation que l'import
    public function test_email_is_saved_in_lowercase_without_spaces(): void
    {
        $id = uniqid();

        $this->add(['email' => "  UserAdd-$id@Test.Local "])->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => "useradd-$id@test.local"]);
    }

    // 3 — Règle B, cas fréquent : le participant de l'an dernier revient
    public function test_email_of_an_archived_user_is_refused_with_its_account(): void
    {
        $email = 'useradd-archive-' . uniqid() . '@test.local';
        $archived = User::create(['lastname' => 'ANCIEN', 'firstname' => 'Joueur', 'email' => $email, 'password' => 'secret']);
        $archived->type = 'archive';
        $archived->save();

        $this->add(['email' => strtoupper($email)])
            ->assertStatus(409)
            ->assertJson(['code' => 'email_pris', 'user_id' => $archived->id, 'type' => 'archive']);

        $this->assertSame(1, User::where('email', $email)->count());
    }

    public function test_email_of_an_active_user_is_refused(): void
    {
        $email = 'useradd-actif-' . uniqid() . '@test.local';
        User::create(['lastname' => 'ACTIF', 'firstname' => 'Joueur', 'email' => $email, 'password' => 'secret']);

        $this->add(['email' => $email])
            ->assertStatus(409)
            ->assertJson(['message' => 'Un compte existe déjà avec cet e-mail.']);
    }

    // 4 — Règle C (la transaction du test annule la désactivation)
    public function test_without_active_event_nothing_is_created(): void
    {
        DB::table('evenements')->where('actif', 1)->update(['actif' => 0]);
        $email = 'useradd-sansconv-' . uniqid() . '@test.local';

        $this->add(['email' => $email])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_invalid_email_is_refused_in_french(): void
    {
        $this->add(['email' => 'pas-un-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => "L'e-mail n'est pas valide"]);
    }
}
