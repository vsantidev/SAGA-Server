<?php

namespace Database\Seeders;

use App\Models\Evenement_user;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EvenementUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '1',
        ]);

        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '2',
        ]);

        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '3',
        ]);

        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '4',
        ]);

        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '5',
        ]);

        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '6',
        ]);

        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '7',
        ]);

        Evenement_user::create([
            'evenement_id' => '1',
            'user_id'  => '8',
        ]);
    }
}
