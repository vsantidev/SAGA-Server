<?php

namespace Database\Seeders;

use App\Models\Animation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnimationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Animation::factory()->create([
            'title' => 'La bière de Barberousse',
            'content' => 'Ce fiéffé coquin a trop bu, 
                aidez-le à retrouver la recette de sa bière planquer dans son galion',
            'evenement_id' => '1',
            'user_id' => '1',
            'fight' => '1',
            'reflection' => '1',
            'roleplay' => '1',
            'room_id' => '1',
            'validate' => '1',
            'capacity' => '40',
            'type_animation_id' => '3',
            'picture'=> "images/img_default.jpg"
        ]);

        Animation::factory()->create([
            'title' => 'La chat-Teigne',
            'content' => 'Le chat nommé Teigne, 
                le digne matou de Slaine est parti en balade ! 
                Retrouvez-le et peut-être vous aurez une chance de pouvoir être récompensé 
                autour une bonne bière par le grand pirate Slaine !',
            'evenement_id' => '1',
            'user_id' => '2',
            'fight' => '3',
            'reflection' => '4',
            'roleplay' => '5',
            'room_id' => '7',
            'capacity' => '30',
            'validate' => '1',
            'type_animation_id' => '2',
            'picture'=> "images/img_default.jpg"
        ]);

        Animation::factory()->create([
            'title' => 'Le secret de la confrérie',
            'content' => 'Un ordre composé des 5 plus grands capitaines 
                vont se rassembler! 
                Rusé pour trouver qui compose cet ordre et comprendre comment y participer 
                afin d\' y découvrir son secret !',
            'evenement_id' => '1',
            'user_id' => '2',
            'type_animation_id' => '2',
            'fight' => '3',
            'reflection' => '5',
            'roleplay' => '5',
            'room_id' => '4',
            'capacity' => '70',
            'validate' => '1',
            'picture'=> "images/img_default.jpg"
        ]);

        Animation::factory()->create([
            'title' => 'Trahison',
            'content' => 'Le capitaine Slaine a été trahi par son bras droit en rejoigant 
                l\'équipage de la sanguinaire Alix ! 
                Préparez-vos plus belles armes car le vainqueur récupèra votre rhum ',
            'evenement_id' => '1',
            'user_id' => '2',
            'type_animation_id' => '4',
            'fight' => '5',
            'reflection' => '2',
            'roleplay' => '5',
            'room_id' => '7',
            'capacity' => '30',
            'validate' => '1',
            'picture'=> "images/img_default.jpg"
        ]);

        Animation::factory()->create([
            'title' => 'Traitre à bord',
            'content' => 'Le but du jeu est simple : de gentils Pirates doivent coopèrer pour remplir un coffre, 
                tandis que de vilains Mutins tentent de saboter leur collecte sans se faire attraper...',
            'evenement_id' => '1',
            'user_id' => '4',
            'type_animation_id' => '2',
            'fight' => '3',
            'reflection' => '4',
            'roleplay' => '5',
            'room_id' => '3',
            'capacity' => '8',
            'validate' => '1',
            'picture'=> "images/img_default.jpg"
        ]);

        Animation::factory()->create([
            'title' => 'Jamaica',
            'content' => ' Faire le tour de l\'île le plus vite possible, en récupérant des marchandises nécessaires à ce genre de course 
                (de l\'or pour les taxes portuaires, 
                de la poudre pour les combats navals et des vivres pour voyager en mer).',
            'evenement_id' => '1',
            'user_id' => '4',
            'type_animation_id' => '2',
            'fight' => '3',
            'reflection' => '5',
            'roleplay' => '5',
            'validate' => '0',
            'picture'=> "images/img_default.jpg"
        ]);

        Animation::factory()->create([
            'title' => 'Le Mount Gay',
            'content' => 'Une rumeur circule que l\'on pourrait trouver le plus ancien rhum du monde!
                Attention pirate, va falloir enquêter si vous voulez trouver cette fameuse bouteille 
                et peut-être vous faire une place dans la confrérie!',
            'evenement_id' => '1',
            'user_id' => '4',
            'type_animation_id' => '3',
            'fight' => '2',
            'reflection' => '5',
            'roleplay' => '5',
            'validate' => '0',
            'picture'=> "images/img_default.jpg"
        ]);
    }
}
