<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Inscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'lastname' => 'Admin',
            'firstname' => 'SAGA',
            'email' => 'tsuyuwolf@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('wolfsecret'),
            'presentation' => 'Administrateur SAGA',
            'picture'=> "images/users/img_default_drake.jpg",
            'type' => 'admin',
        ]);

        $this->call([
            SiteSeeder::class,
            EvenementSeeder::class,
            RoomSeeder::class,
            AnimationSeeder::class,
            TypeAnimationSeeder::class,
            InscriptionSeeder::class,
            SponsorSeeder::class,
            UserSeeder::class,
        ]);

    }
}
