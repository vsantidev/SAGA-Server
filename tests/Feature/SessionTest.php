<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

// Session (jeton Sanctum) :
// A. une requête de l'API sans session valable reçoit un 401 en JSON, même sans en-tête Accept ;
// B. la déconnexion ne ferme que la session de l'appareil qui se déconnecte.
class SessionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Session',
            'email'     => 'session-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
    }

    private function callWith(string $token, string $method = 'GET', string $uri = '/api/user/token')
    {
        // Le garde Sanctum garde l'utilisateur en mémoire d'une requête à l'autre : on l'oublie
        $this->app['auth']->forgetGuards();

        return $this->call($method, $uri, [], [], [], ['HTTP_AUTHORIZATION' => "Bearer $token"]);
    }

    // A — sans « Accept: application/json », comme le font la plupart des écrans
    public function test_revoked_token_gets_json_401(): void
    {
        $response = $this->callWith('1|jeton-revoque-ou-invente');

        $response->assertStatus(401);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('Session expirée ou invalide : reconnectez-vous.', $response->json('message'));
    }

    // B
    public function test_logout_closes_only_the_current_session(): void
    {
        $telephone = $this->user->createToken('telephone')->plainTextToken;
        $ordinateur = $this->user->createToken('ordinateur')->plainTextToken;

        $this->callWith($telephone, 'POST', '/api/logout')->assertStatus(200);

        $this->callWith($telephone)->assertStatus(401);
        $this->callWith($ordinateur)->assertStatus(200);
    }
}
