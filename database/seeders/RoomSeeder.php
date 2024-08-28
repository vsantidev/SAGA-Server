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
            'name' => 'BILLARD',
            'building' => 'Chapelle',
            'capacity' => '6',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'La table de billard à un plateau mis est trés haute',
        ]);

        Room::factory()->create([
            'name' => 'FORUM/AGORA',
            'building' => 'Extérieur',
            'capacity' => '150',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
        ]);

        
        Room::factory()->create([
        'name' => 'GRAND SALON',
        'building' => 'Principal',
        'capacity' => '30',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        'description' => 'Espace pour les expositions des partenaires (présentoirs), les tables rondes et jusqu\'a 2 tables de JDR',
        ]);

        Room::factory()->create([
            'name' => 'GRAND COUR',
            'building' => 'Extérieur',
            'capacity' => '120',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Espace pour les expositions des partenaires (présentoirs), les tables rondes et jusqu\'a 2 tables de JDR',
        ]);

        Room::factory()->create([
            'name' => 'GRANDE OURSE - ESTRADE',
            'building' => 'PRINCIPAL',
            'capacity' => '25',
            'floor' => '2',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Espace dédié au jeu de plateau, permet d\'accueillir 3-4 tables de jeu',
        ]);
        
        Room::factory()->create([
            'name' => 'GRANDE OURSE - HAUT',
            'building' => 'PRINCIPAL',
            'capacity' => '10',
            'floor' => '2',
            'pmr' => '0',
            'site_id' => '1',
            'description' => 'Grande table ronde pour accueillir jusqu\'a 10 personnes',
        ]);

        Room::factory()->create([
            'name' => 'GRANDE OURSE - SALON',
            'building' => 'PRINCIPAL',
            'capacity' => '8',
            'floor' => '2',
            'pmr' => '0',
            'site_id' => '1',
            'description' => 'Canapés et une table basse, parfait pour accueillir les petits jeux apéro en gestion libre',
        ]);

        Room::factory()->create([
            'name' => 'HAUT PLATEAU',
            'building' => 'Extérieur',
            'capacity' => '200',
            'floor' => '',
            'pmr' => '0',
            'site_id' => '1',
            'description' => 'Prenez de quoi avoir chaud',
        ]);

        Room::factory()->create([
        'name' => 'LERINS',
        'building' => 'Principal',
        'capacity' => '6',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        'description' => 'Salle accessible par le cloître, proche de la chapelle. Elle est petite, idéale pour 1 MJ et 4 joueurs mais en se serrant un peu ca passe à 6',
        ]);

        Room::factory()->create([
            'name' => 'NEF CHAPELLE',
            'building' => 'Chapelle',
            'capacity' => '30',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Cet espace est dédié au wargame, à la fois pour les tournois du week-end et les initiations',
            ]);

        Room::factory()->create([
            'name' => 'ORANGERAIE',
            'building' => 'Extérieur',
            'capacity' => '12',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Serre vitrée dans la cours du domaine, à chauffer en hiver. Parfait pour du JDR au calme',
            ]);

        Room::factory()->create([
            'name' => 'PETIT SALON',
            'building' => 'Principal',
            'capacity' => '10',
            'floor' => '1',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Dédié aux murders du samedi après-midi et soir (10-12 personnes max), le reste du temps utilisable pour jdr',
        ]);

        Room::factory()->create([
            'name' => 'PLEIADES',
            'building' => 'Principal',
            'capacity' => '8',
            'floor' => '1',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Dédié aux jdr',
        ]);

        Room::factory()->create([
            'name' => 'REFECTOIRE ENTREE',
            'building' => 'Principal',
            'capacity' => '30',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Afin de ne pas empêcher la mise en place des repas, espace à dédier aux animations du soir après les repas. Résonne donc 2 parties max dans l\'ensemble du refectoire',
        ]);

        Room::factory()->create([
            'name' => 'REFECTOIRE FOND',
            'building' => 'Principal',
            'capacity' => '30',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Afin de ne pas empêcher la mise en place des repas, espace à dédier aux animations du soir après les repas. Résonne donc 2 parties max dans l\'ensemble du refectoire',
        ]);

        Room::factory()->create([
            'name' => 'SALLE DE SPORT',
            'building' => 'Piscine',
            'capacity' => '6',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Afin de ne pas empêcher la mise en place des repas, espace à dédier aux animations du soir après les repas. Résonne donc 2 parties max dans l\'ensemble du refectoire',
        ]);


        Room::factory()->create([
        'name' => 'SENANQUE',
        'building' => 'Principal',
        'capacity' => '8',
        'floor' => '1',
        'pmr' => '1',
        'site_id' => '1',
        'description' => 'Bibliothèque - Espace dédié au JDR accessible par une petite cours derrière le bâtiment'
        ]);

        Room::factory()->create([
            'name' => 'Silvacane',
            'building' => 'Principal',
            'capacity' => '8',
            'floor' => '1',
            'pmr' => '1',
            'site_id' => '1',
            'description' => 'Espace dédié à l\'escape game, accessible par une petite cours derrière le batiment',
            ]);
        
        Room::factory()->create([
        'name' => 'SIRIUS BIS',
        'building' => 'Principal',
        'capacity' => '5',
        'floor' => '2',
        'pmr' => '1',
        'site_id' => '1',
        'description' => 'Petite pièce au milieu des espaces chambres, pour peu de joueurs, en JDR',
        ]);

        Room::factory()->create([
            'name' => 'TERRASSE',
            'building' => 'Extérieur',
            'capacity' => '50',
            'floor' => '0',
            'pmr' => '1',
            'site_id' => '1',
            ]);

        

    }
}
