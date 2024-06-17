<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Site::factory()->create([
            'name' => 'Monastère de Ségries',
            'street' => '6298 Rte de Riez',
            'postcode' => '04360',
            'city' => 'Moustiers-Sainte-Marie',
        ]);
    }
}
