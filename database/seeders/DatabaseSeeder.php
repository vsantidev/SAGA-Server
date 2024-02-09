<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory()->create([
        //     'firstname' => 'SerVer',
        //     'lastname' => 'Nier',
        //     'email' => 'Servertest@example.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('topsecret'),
        //     'birthday' => '2017/02/23',
        //     'type'=> 'animateur',
        // ]);

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

    }
}
