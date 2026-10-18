<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Changement de mot de passe (Compte > Mot de passe) et connexion :
// A. le nouveau mot de passe fait au moins 6 caractères et les deux saisies sont identiques ;
// B. ce que le joueur tape est ce qui est enregistré (pas d'espaces retirés), comme au login ;
// C. les refus répondent 422 avec un message ;
// D. la connexion avec un mot de passe vide est refusée.
class UserMdpTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Mdp',
            'email'     => 'usermdp-' . uniqid() . '@test.local',
            'password'  => 'Ancien2026!',
        ]);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    // Même corps que l'écran actuel (multipart, avec user_id, ignoré par le serveur)
    private function changeMdp(string $old, ?string $new1, ?string $new2)
    {
        return $this->withToken($this->token)->post('/api/user/mdp', [
            'user_id'      => $this->user->id,
            'oldPassword'  => $old,
            'newPassword1' => $new1,
            'newPassword2' => $new2,
        ]);
    }

    private function passwordIs(string $password): bool
    {
        return Hash::check($password, $this->user->fresh()->password);
    }

    // 1 — Règle A : le cas de la carte
    public function test_empty_new_password_is_refused(): void
    {
        $this->changeMdp('Ancien2026!', '', '')
            ->assertStatus(422)
            ->assertJson(['status' => 'false']);

        $this->assertTrue($this->passwordIs('Ancien2026!'));
    }

    public function test_password_of_spaces_is_refused(): void
    {
        $this->changeMdp('Ancien2026!', '   ', '   ')->assertStatus(422);

        $this->assertTrue($this->passwordIs('Ancien2026!'));
    }

    public function test_password_shorter_than_6_characters_is_refused(): void
    {
        $this->changeMdp('Ancien2026!', 'Abc12', 'Abc12')
            ->assertStatus(422)
            ->assertJson(['message' => 'Le mot de passe doit faire au moins 6 caractères.']);

        $this->assertTrue($this->passwordIs('Ancien2026!'));
    }

    public function test_password_of_6_characters_is_accepted(): void
    {
        $this->changeMdp('Ancien2026!', 'Abc123', 'Abc123')->assertStatus(200);

        $this->assertTrue($this->passwordIs('Abc123'));
    }

    // 2 — Règle C
    public function test_different_confirmation_is_refused(): void
    {
        $this->changeMdp('Ancien2026!', 'Nouveau2026!', 'Nouveau2026?')
            ->assertStatus(422)
            ->assertJson(['message' => 'Les deux saisies du nouveau mot de passe ne sont pas identiques.']);

        $this->assertTrue($this->passwordIs('Ancien2026!'));
    }

    public function test_wrong_old_password_is_refused(): void
    {
        $this->changeMdp('Faux2026!', 'Nouveau2026!', 'Nouveau2026!')
            ->assertStatus(422)
            ->assertJson(['message' => 'Ancien mot de passe incorrect.']);

        $this->assertTrue($this->passwordIs('Ancien2026!'));
    }

    // 3 — Règles A et B : changement accepté, l'espace finale est gardée
    public function test_password_is_changed_as_typed(): void
    {
        $this->changeMdp('Ancien2026!', 'Secret2026! ', 'Secret2026! ')
            ->assertStatus(200)
            ->assertJson(['status' => 'true']);

        $this->postJson('/api/login', ['email' => $this->user->email, 'password' => 'Secret2026! '])
            ->assertStatus(200)
            ->assertJsonStructure(['token']);
        $this->assertFalse($this->passwordIs('Secret2026!'));
    }

    // 4 — Règle D
    public function test_login_with_empty_password_is_refused(): void
    {
        $this->postJson('/api/login', ['email' => $this->user->email, 'password' => ''])
            ->assertStatus(422)
            ->assertJsonMissingPath('token');
    }
}
