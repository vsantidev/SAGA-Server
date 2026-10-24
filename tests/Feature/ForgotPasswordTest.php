<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Mot de passe oublié :
// A. la demande ne change pas le mot de passe : elle envoie un lien à usage unique, valable 60 minutes ;
// B. le lien permet de choisir un nouveau mot de passe (6 caractères minimum), une seule fois,
//    et ferme les sessions ouvertes ;
// C. un faux lien est refusé ; une 2e demande dans la minute est refusée.
class ForgotPasswordTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Oubli',
            'email'     => 'oubli-' . uniqid() . '@test.local',
            'password'  => 'Ancien2026',
        ]);
    }

    private function askLink()
    {
        return $this->postJson('/api/forgot-password', ['email' => $this->user->email]);
    }

    // Jeton tiré du lien du dernier e-mail envoyé
    private function tokenFromMail(): string
    {
        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertGreaterThan(0, $messages->count(), 'aucun e-mail envoyé');
        $html = $messages->last()->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString(rtrim(config('app.front_url'), '/') . '/reset-password?token=', $html);
        preg_match('/token=([0-9a-f]+)&amp;email=/', $html, $m);
        $this->assertNotEmpty($m, 'lien introuvable dans l’e-mail');

        return $m[1];
    }

    private function reset(string $token, string $password, ?string $confirmation = null)
    {
        return $this->postJson('/api/reset-password', [
            'email'                 => $this->user->email,
            'token'                 => $token,
            'password'              => $password,
            'password_confirmation' => $confirmation ?? $password,
        ]);
    }

    // 1 — Règle A : demander un lien ne touche pas au mot de passe actuel
    public function test_request_keeps_current_password_and_sends_link(): void
    {
        $this->askLink()->assertStatus(200);

        $this->assertTrue(Hash::check('Ancien2026', $this->user->fresh()->password));
        $this->tokenFromMail();
    }

    // 2 — Règle B : le lien change le mot de passe et ferme les sessions
    public function test_link_sets_new_password_and_closes_sessions(): void
    {
        $this->user->createToken('appareil');
        $this->askLink()->assertStatus(200);

        $this->reset($this->tokenFromMail(), 'Nouveau2026')->assertStatus(200);

        $this->assertTrue(Hash::check('Nouveau2026', $this->user->fresh()->password));
        $this->assertSame(0, $this->user->tokens()->count());
        $this->postJson('/api/login', ['email' => $this->user->email, 'password' => 'Nouveau2026'])->assertStatus(200);
    }

    // 3 — Règle B : une seule fois
    public function test_link_works_only_once(): void
    {
        $this->askLink();
        $token = $this->tokenFromMail();

        $this->reset($token, 'Nouveau2026')->assertStatus(200);
        $this->reset($token, 'Encore2026')->assertStatus(422);

        $this->assertTrue(Hash::check('Nouveau2026', $this->user->fresh()->password));
    }

    // 4 — Règle B : mêmes règles que le changement de mot de passe
    public function test_new_password_rules(): void
    {
        $this->askLink();
        $token = $this->tokenFromMail();

        $this->reset($token, '12345')->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Le mot de passe doit faire au moins 6 caractères.');
        $this->reset($token, 'Nouveau2026', 'Autre2026')->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Les deux saisies du nouveau mot de passe ne sont pas identiques.');

        $this->assertTrue(Hash::check('Ancien2026', $this->user->fresh()->password));
    }

    // 5 — Règle C : un jeton inventé est refusé
    public function test_fake_token_is_refused(): void
    {
        $this->askLink();

        $this->reset(str_repeat('a', 64), 'Nouveau2026')->assertStatus(422);
        $this->assertTrue(Hash::check('Ancien2026', $this->user->fresh()->password));
    }

    // 6 — Règle C : une 2e demande dans la minute n'envoie rien
    public function test_second_request_within_a_minute_is_refused(): void
    {
        $this->askLink()->assertStatus(200);
        $this->askLink()->assertStatus(429);

        $this->assertSame(1, app('mailer')->getSymfonyTransport()->messages()->count());
    }
}
