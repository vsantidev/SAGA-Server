<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Limites de requêtes de l'API :
// A. un compte connecté n'est pas bloqué par un usage normal de l'écran (8 requêtes par vœu) ;
// B. une salle entière derrière la même adresse (wifi, borne satellite) peut se connecter ;
// C. les mauvais mots de passe sur un même compte restent bloqués ; un refus de limite est en français ;
// D. « mot de passe oublié » reste strict par adresse e-mail.
class RateLimitTest extends TestCase
{
    use DatabaseTransactions;

    // A — 70 requêtes dans la minute : refusé avant le correctif (limite de 60)
    public function test_connected_account_is_not_blocked_by_normal_use(): void
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Limite',
            'email'     => 'limite-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($user);

        for ($i = 1; $i <= 70; $i++) {
            $response = $this->getJson('/api/user/token');
            $this->assertNotSame(429, $response->status(), "requête $i refusée");
        }
    }

    // B — 100 personnes différentes se connectent depuis la même adresse dans la minute
    public function test_many_people_behind_one_address_can_log_in(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            $response = $this->postJson('/api/login', [
                'email'    => "salle-$i-" . uniqid() . '@test.local',
                'password' => 'pas-le-bon',
            ]);
            $this->assertNotSame(429, $response->status(), "connexion $i refusée");
        }
    }

    // C — les mauvais mots de passe sur un même compte restent bloqués (5 échecs, AuthController)
    public function test_repeated_wrong_passwords_on_one_account_are_still_blocked(): void
    {
        $email = 'essais-' . uniqid() . '@test.local';
        for ($i = 1; $i <= 4; $i++) {
            $this->postJson('/api/login', ['email' => $email, 'password' => "faux-$i"])->assertStatus(401);
        }

        $this->postJson('/api/login', ['email' => $email, 'password' => 'faux-5'])->assertStatus(429);
        $this->postJson('/api/login', ['email' => $email, 'password' => 'faux-6'])->assertStatus(429);
    }

    // C bis — une limite de l'API atteinte répond en français, avec le délai d'attente
    public function test_rate_limit_message_is_in_french(): void
    {
        $email = 'oubli-fr-' . uniqid() . '@test.local';
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/forgot-password', ['email' => $email]);
        }

        $response = $this->postJson('/api/forgot-password', ['email' => $email]);

        $response->assertStatus(429);
        $this->assertStringStartsWith('Trop de requêtes : patientez', $response->json('message'));
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    // D — « mot de passe oublié » : 3 demandes par adresse e-mail et par minute
    public function test_forgot_password_is_limited_per_email(): void
    {
        $email = 'oubli-' . uniqid() . '@test.local';
        for ($i = 1; $i <= 3; $i++) {
            $this->assertNotSame(429, $this->postJson('/api/forgot-password', ['email' => $email])->status());
        }

        $this->postJson('/api/forgot-password', ['email' => $email])->assertStatus(429);
        // Une autre adresse e-mail n'est pas touchée
        $this->assertNotSame(429, $this->postJson('/api/forgot-password', ['email' => 'autre-' . $email])->status());
    }
}
