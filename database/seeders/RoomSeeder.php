<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Room::factory()->create([
        'name' => 'Grand salon',
        'capacity' => '50',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        ]);

        Room::factory()->create([
        'name' => 'Silvacane',
        'capacity' => '20',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        ]);

        Room::factory()->create([
        'name' => 'Lérins',
        'capacity' => '10',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        ]);

        Room::factory()->create([
        'name' => 'Chapelle',
        'capacity' => '150',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        ]);

        Room::factory()->create([
        'name' => 'Petit salon',
        'capacity' => '25',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        ]);

        Room::factory()->create([
        'name' => 'Sénanque',
        'capacity' => '20',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        ]);
        
        Room::factory()->create([
        'name' => 'Grand’Ourse',
        'capacity' => '40',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        ]);

    }
}
