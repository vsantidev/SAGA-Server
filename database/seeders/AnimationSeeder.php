<?php

namespace Database\Seeders;

use App\Models\Animation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnimationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Animation::factory()->create([
        'title' => 'La biere de Barberousse',
        'content' => 'Ce fiéffé coquin a trop bu, aidez le à la retrouver',
        'evenement_id' => '1',
        'user_id' => '1',
        'room_id' => '1',
        'fight' => '1',
        'reflection' => '1',
        'roleplay' => '1',
        'type_animation_id' => '1',
        'picture'=> "images/img_default.jpg"
        ]);
    }
}
