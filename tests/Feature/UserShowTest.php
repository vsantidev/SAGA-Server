<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Lecture d'un profil (userShow) :
// A. un joueur lit son propre profil ;
// B. le profil d'un autre compte est réservé aux admins ;
// C. un admin lit n'importe quel profil ;
// D. la réponse ne contient que les champs affichés par les écrans.
class UserShowTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $type = 'membre'): User
    {
        $user = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Profil',
            'email'     => 'usershow-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $user->type = $type;
        $user->phone = '0600000000';
        $user->save();

        return $user;
    }

    // 1 — Règle A
    public function test_player_reads_own_profile(): void
    {
        $player = $this->makeUser();
        Sanctum::actingAs($player);

        $response = $this->getJson("/api/user/userprofile/{$player->id}");

        $response->assertOk()
            ->assertJson(['id' => $player->id, 'email' => $player->email, 'phone' => '0600000000', 'type' => 'membre'])
            ->assertJsonMissingPath('password');
    }

    // 2 — Règle B
    public function test_player_cannot_read_another_profile(): void
    {
        $player = $this->makeUser();
        $other = $this->makeUser();
        Sanctum::actingAs($player);

        $response = $this->getJson("/api/user/userprofile/{$other->id}");

        $response->assertStatus(403)
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('password');
    }

    // 3 — Règle C
    public function test_admin_reads_another_profile(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser();
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/user/userprofile/{$other->id}");

        $response->assertOk()
            ->assertJson(['id' => $other->id, 'email' => $other->email, 'phone' => '0600000000'])
            ->assertJsonMissingPath('password');
    }

    public function test_unknown_profile_returns_404(): void
    {
        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson('/api/user/userprofile/999999999')->assertNotFound();
    }
}
