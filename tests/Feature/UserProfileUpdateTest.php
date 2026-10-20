<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\Evenement_user;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Modification d'un profil (userUpdate : le joueur ; userAdminUpdate : l'admin) :
// A. e-mail obligatoire, valide et libre, nom et prénom présents : sinon 422 avec un message ;
// B. date de naissance facultative (vide = pas de date) ;
// C. côté admin, type limité aux valeurs connues, et un compte archivé n'est pas rattaché
//    à la convention active.
class UserProfileUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private User $joueur;
    private User $autre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->joueur = $this->makeUser('joueur');
        $this->autre = $this->makeUser('autre');
        Sanctum::actingAs($this->joueur);
    }

    private function makeUser(string $name): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => ucfirst($name),
            'email'     => "profil-$name-" . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = 'membre';
        $user->save();

        return $user;
    }

    // Le profil joueur part en JSON dans le champ « user » d'un formulaire
    private function saveOwn(array $changes = [])
    {
        $user = array_merge([
            'id'           => $this->joueur->id,
            'lastname'     => $this->joueur->lastname,
            'firstname'    => $this->joueur->firstname,
            'birthday'     => '1990-05-12',
            'phone'        => '0600000000',
            'email'        => $this->joueur->email,
            'presentation' => 'Bonjour',
        ], $changes);

        return $this->post('/api/user/userprofile/' . $this->joueur->id, ['user' => json_encode($user)], ['Accept' => 'application/json']);
    }

    private function saveAsAdmin(User $user, array $changes = [])
    {
        return $this->postJson('/api/user/userprofileadmin/' . $user->id, array_merge([
            'id'           => $user->id,
            'lastname'     => $user->lastname,
            'firstname'    => $user->firstname,
            'phone'        => null,
            'email'        => $user->email,
            'presentation' => null,
            'type'         => $user->type,
            'is_orga'      => 0,
        ], $changes));
    }

    // 1 — Règle A, joueur
    public function test_own_profile_refuses_email_of_another_account(): void
    {
        $this->saveOwn(['email' => $this->autre->email])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cette adresse e-mail est déjà utilisée par un autre compte.');

        $this->assertNotSame($this->autre->email, $this->joueur->fresh()->email);
    }

    public function test_own_profile_refuses_invalid_email(): void
    {
        $this->saveOwn(['email' => 'pas-un-email'])->assertStatus(422);
        $this->saveOwn(['email' => ''])->assertStatus(422);
    }

    // 2 — Règle B : date vide acceptée, enregistrée sans date
    public function test_own_profile_accepts_empty_birthday(): void
    {
        $this->saveOwn(['birthday' => ''])->assertStatus(200);

        $this->assertNull($this->joueur->fresh()->birthday);
    }

    // Garder son propre e-mail n'est pas un doublon
    public function test_own_profile_saves_with_unchanged_email(): void
    {
        $this->saveOwn(['presentation' => 'Nouvelle présentation'])->assertStatus(200);

        $this->assertSame('Nouvelle présentation', $this->joueur->fresh()->presentation);
    }

    // 3 — Règle A et C, admin
    public function test_admin_update_checks_email_and_type(): void
    {
        $this->saveAsAdmin($this->autre, ['email' => ''])->assertStatus(422);
        $this->saveAsAdmin($this->autre, ['email' => $this->joueur->email])->assertStatus(422);
        $this->saveAsAdmin($this->autre, ['type' => 'n_importe_quoi'])->assertStatus(422);

        $this->assertSame('membre', $this->autre->fresh()->type);
    }

    public function test_archived_account_is_not_attached_to_active_event(): void
    {
        $evenementId = Evenement::where('actif', 1)->value('id');

        $this->saveAsAdmin($this->autre, ['type' => 'archive'])->assertStatus(200);

        $this->assertSame('archive', $this->autre->fresh()->type);
        $this->assertFalse(Evenement_user::where('evenement_id', $evenementId)->where('user_id', $this->autre->id)->exists());
    }

    // Comportement gardé : un compte actif modifié par l'admin est rattaché à la convention active
    public function test_active_account_is_still_attached(): void
    {
        $evenementId = Evenement::where('actif', 1)->value('id');

        $this->saveAsAdmin($this->autre, ['type' => 'animateur'])->assertStatus(200);

        $this->assertTrue(Evenement_user::where('evenement_id', $evenementId)->where('user_id', $this->autre->id)->exists());
    }
}
