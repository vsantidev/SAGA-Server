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
            'time' => '2',
            'capacity' => '5',
            'type_animation_id' => '3',
            'picture'=> "images/img_default.jpg",
            'registration_date' => "2024-11-29 10:00:00",
            'open_time' => "2024-11-29 14:00:00",
            'closed_time' => "2024-11-29 16:00:00"
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
            'time' => '2',
            'capacity' => '4',
            'validate' => '1',
            'type_animation_id' => '2',
            'picture'=> "images/img_default.jpg",
            'registration_date' => "2024-11-29 08:00:00",
            'open_time' => "2024-11-29 10:00:00",
            'closed_time' => "2024-11-29 12:00:00"
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
            'time' => '2',
            'capacity' => '3',
            'validate' => '1',
            'picture'=> "images/img_default.jpg",
            'registration_date' => "2024-11-29 10:00:00",
            'open_time' => "2024-11-29 14:00:00",
            'closed_time' => "2024-11-29 16:00:00"
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
            'time' => '2',
            'capacity' => '2',
            'validate' => '1',
            'picture'=> "images/img_default.jpg",
            'registration_date' => "2024-11-29 10:00:00",
            'open_time' => "2024-11-29 14:00:00",
            'closed_time' => "2024-11-29 16:00:00"
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
            'time' => '2',
            'capacity' => '1',
            'validate' => '1',
            'picture'=> "images/img_default.jpg",
            'registration_date' => "2024-11-29 20:00:00",
            'open_time' => "2024-11-30 08:00:00",
            'closed_time' => "2024-11-30 10:00:00"
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
            'time' => '2',
            'reflection' => '5',
            'capacity' => '1',
            'roleplay' => '5',
            'validate' => '0',
            'picture'=> "images/img_default.jpg",
            'open_time' => "2024-11-30 10:00:00",
            'closed_time' => "2024-11-30 12:00:00"
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
            'capacity' => '1',
            'time' => '2',
            'roleplay' => '5',
            'validate' => '0',
            'picture'=> "images/img_default.jpg",
            'open_time' => "2024-11-30 14:00:00",
            'closed_time' => "2024-11-30 16:00:00"
        ]);


        Animation::factory()->create([
            'title' => 'Découverte de 5 Whisky tourbé',
            'content' => "Je vous propose de venir voyager en terre écossaise afin de découvrir ou redécouvrir pour certains 5 Whisky tourbés.

            Après avoir rapidement évoqué quelques éléments propre à cette noble boisson nous dégusterons 5 verres afin d\'échanger autour de nos perceptions de ces Whisky.
            
            Les 5 whisky proposés sont:
            
            - Artist 7 - Compass Box - Edition limité la Maison du Whisky
            - 3D3 Norrie Campbell Tribute Bottling - Bruichladdich
            - Ardmore 2009 - Selection le Gus\'t - Un-Chillfiltered Signatory
            - Moine French Oak Finish - Feis Ile 2019 - Bunnahabhain
            - Ledaig 2005 11 ans - Cask Strenght Collection Signatory
            
            Tous sont des Whisky peu commun pas toujours facile à trouvé (en moyenne moins de 2000 bouteilles, voir beaucoup moins pour certains). Tous présente un aspect différent et des goûts variés.",
            'remark' => "Ce sont des Whisky d'exception, une participation financière vous sera demandée. Mineurs exclus. Il s'agit d'alcool donc attention à ne pas en abuser.",
            'evenement_id' => '1',
            'user_id' => '4',
            'type_animation_id' => '3',
            'fight' => '2',
            'reflection' => '5',
            'capacity' => '1',
            'roleplay' => '5',
            'validate' => '0',
            'time' => '2',
            'picture'=> "images/img_default.jpg",
            'open_time' => "2024-11-30 14:00:00",
            'closed_time' => "2024-11-30 16:00:00"
        ]);
    }
}
