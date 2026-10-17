<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LikeShowTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsUser(): User
    {
        $user = User::create([
            'lastname'  => 'Test',
            'firstname' => 'Like',
            'email'     => 'like-show-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    // La liste des likes d'une animation renvoie les user_id qui l'ont likée.
    public function test_show_like_returns_users_who_liked_the_animation(): void
    {
        $user = $this->actingAsUser();
        $animationId = Animation::value('id');
        DB::table('likes')->insert([
            'user_id'      => $user->id,
            'animation_id' => $animationId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->getJson('/api/like/animationIndex?id=' . $animationId)
            ->assertStatus(200)
            ->assertJsonFragment(['user_id' => $user->id]);
    }

    // Sans id d'animation, l'API répond 422 au lieu de planter.
    public function test_show_like_without_id_returns_422(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/like/animationIndex')->assertStatus(422);
    }
}
