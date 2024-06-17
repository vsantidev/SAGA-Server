<?php

namespace Database\Seeders;

use App\Models\Evenement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EvenementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Evenement::factory()->create([
            'actif' => '1',
            'title' => 'IV : PIRATES',
            'date_opening' => '2024/11/29',
            'date_ending' => '2024/12/01',
            'site_id' => '1'
        ]);
       
    }
}
