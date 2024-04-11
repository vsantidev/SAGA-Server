<?php

namespace Database\Seeders;

use App\Models\Type_animation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeAnimationSeeder extends Seeder
{

    private $etiquette = ['Grandeur Nature', 'Jeux de Rôles', 'Jeux de plateau', 'Murder Party']; 

    public function run(): void
    {
        foreach ($this->etiquette as $etiquette) {
            Type_animation::create([
                'type' => $etiquette]);
        }

        echo array_rand($this->etiquette);
    }
}
