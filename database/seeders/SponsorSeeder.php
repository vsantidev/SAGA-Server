<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SponsorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sponsor::factory()->create([
            'name' => 'Arkhame Asylum Edition',
            'content' => 'Arkhane Asylum Publishing est un éditeur de jeux de rôle spécialisé dans des univers immersifs et variés.
                          Survivez dans les Terres Désolées de Fallout, le jeu de rôle, explorez des mondes étranges dans Dune: Aventures dans l\'Imperium, affrontez des créatures redoutables dans The Witcher, 
                          succombez à la peur dans Alien, le jeu de rôle, ou plongez dans les ténèbres avec Vampire: la Mascarade.
                          Découvrez aussi le jeu de rôle Avatar Légendes, inspiré du célèbre dessin animé ou Cyberpunk RED, le jeu à l\'origine de Cyberpunk 2077.
                          Avec Arkhane Asylum, vivez des aventures épiques et inoubliables grâce à nos différentes gammes de jeux de rôle et d\'accessoires.
                          Rejoignez-nous pour des heures de divertissement captivant et des expériences de jeu uniques. ',
            'picture'=> "images/sponsors/66c5bcf1eabd8_LogoArkhaneAsylumPublishing.png",
            'link' => 'https://www.arkhane-asylum.fr/',
        ]);

        Sponsor::factory()->create([
            'name' => 'Bandjo!',
            'content' => 'Bandjo, c’est le nom souriant d’un studio de création de jeux de société français, lancé début 2024, suite à l’intégration de la maison d’édition Le Droit de Perdre au sein du groupe Blue Orange.
                          Bandjo, c’est aussi l’abréviation de « Bande joyeuse » et cette joie qui anime notre équipe, nous souhaitons vous l’apporter au travers de nos différents jeux de société, malins, festifs et insolites.',
            'picture'=> "images/sponsors/66c5bd852b6d7_Droit de perdre.png",
            'link' => 'https://bandjo.games/',
        ]);

        Sponsor::factory()->create([
            'name' => 'Elder Craft',
            'content' => 'Notre ambition est d’initier, de partager et de faire découvrir. Nous voulons construire des passerelles qui amènent les gens à découvrir des loisirs incomparablement riches, mais qui, faute d’accessibilité, n’occupent pas la place qu’ils méritent dans la société de loisirs et l’industrie de la culture. 
                          Fabriquer le souvenir d’une expérience narrative comparable à n’importe quel autre support : roman, bd, comics, film, jeu vidéo… Nous considérons le jeu comme un autre moyen de raconter une histoire. Mieux, de la vivre.',
            'picture'=> "images/sponsors/66c5be37ac834_elder_craft.png",
            'link' => 'https://elder-craft.com/',
        ]);
    }
}
