<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'lastname' => 'mon',
            'firstname' => 'Administrateur',
            'birthday' => '2017/02/23',
            'email' => 'serverTest@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('MonSuperMotDePasse123'),
            'presentation' => 'Le mini administrateur',
            'type' => 'admin',
        ]);

        User::factory()->create([
            'lastname' => 'Wyvern',
            'firstname' => 'Tsuyu',
            'birthday' => '1987/09/04',
            'email' => 'tsuyuwolf@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('wolfsecret'),
            'presentation' => 'The AïeAdministrator',
            'type' => 'admin',
        ]);

        User::factory()->create([
            'lastname' => 'Senai',
            'firstname' => 'Koro',
            'birthday' => '1995/05/29',
            'email' => 'Koro.Sensei@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Y@mete1'),
            'presentation' => 'The teacher mystery',
            'type' => 'membre',
        ]);

        $this->call([
            SiteSeeder::class,
            EvenementSeeder::class,
            RoomSeeder::class,
            AnimationSeeder::class,
            TypeAnimationSeeder::class,
        ]);

    }
}
