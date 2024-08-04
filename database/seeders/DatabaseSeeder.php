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
        $this->call([
            SiteSeeder::class,
            EvenementSeeder::class,
            RoomSeeder::class,
            AnimationSeeder::class,
            TypeAnimationSeeder::class,
            InscriptionSeeder::class,
            UserSeeder::class,
        ]);

    }
}
