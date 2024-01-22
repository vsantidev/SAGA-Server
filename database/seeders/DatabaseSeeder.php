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
            'firstname' => 'Tsuyu',
            'lastname' => 'Wyvern',
            'email' => 'tsuyuwolf@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('wolfsecret'),
            'birthday' => '1987/09/04',
            'type'=> 'admin',
        ]);

        // User::factory()->create([
        //     'firstname' => 'Lulu',
        //     'lastname' => 'Lemon',
        //     'email' => 'LuluLemon@example.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('lulusecret'),
        //     'birthday' => '2000/05/29',
        //     'type'=> 'membre',
        // ]);

    }
}
