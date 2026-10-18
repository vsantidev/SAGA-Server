<?php

namespace App\Services;

use App\Models\Evenement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsService
{
    /**
     * Renvoie les statistiques brutes des animations
     * liées à l'événement actif
     */
    public function getRawAnimationsStats()
    {
        // Récupération de l'événement actif
        $evenementActif = Evenement::where('actif', 1)->first();

        if (!$evenementActif) {
            return collect(); // renvoie une collection vide si aucun événement actif
        }

        // Requête : uniquement les animations de l'événement actif
        return DB::table('animations')
            ->select(
                'animations.id',
                'animations.title',
                'animations.open_time',
                'animations.closed_time',
                'animations.capacity',
            )
            ->where('animations.evenement_id', $evenementActif->id)
            ->groupBy(
                'animations.id',
                'animations.title',
                'animations.open_time',
                'animations.closed_time',
                'animations.capacity'
            )
            ->orderBy('animations.open_time', 'asc')
            ->get();
    }

    


    /*public function getCapacityByTranche()
    {
        $evenementActif = Evenement::where('actif', 1)->first();

        if (!$evenementActif) {
            return [
                'message' => 'Aucun évènement actif',
            ];
        }

        // Récupération de toutes les animations de l'événement actif
        $animations = DB::table('animations')
            ->where('evenement_id', $evenementActif->id)
            ->select('capacity', 'open_time')
            ->get();

        // Initialisation des tranches
        $tranches = [
            'Matin' => ['capacity' => 0],
            'Après-midi' => ['capacity' => 0],
            'Soir' => ['capacity' => 0],
        ];

        // Calcul de la capacité par tranche
        foreach ($animations as $anim) {
            $hour = \Carbon\Carbon::parse($anim->open_time)->hour;
            if ($hour >= 8 && $hour < 14) $tranche = 'Matin';
            elseif ($hour >= 14 && $hour < 19) $tranche = 'Après-midi';
            elseif ($hour >= 19) $tranche = 'Soir';
            else continue;

            $tranches[$tranche]['capacity'] += $anim->capacity;
        }

        // 🔹 Récupération du nombre total de participants sur l'événement actif
        $totalParticipants = DB::table('evenement_users')
            ->where('evenement_id', $evenementActif->id)
            ->count();

        // 🔹 Calcul des places manquantes par tranche
        foreach ($tranches as $key => $t) {
            $tranches[$key]['participants'] = $totalParticipants;
            $tranches[$key]['missing_places'] = max(0, $totalParticipants - $t['capacity']);
            $tranches[$key]['surplus_places'] = max(0, $t['capacity'] - $totalParticipants);
        }

        return [
            'evenement_id' => $evenementActif->id,
            'evenement_nom' => $evenementActif->name ?? 'Événement actif',
            'tranches' => $tranches,
            'total_participants' => $totalParticipants,
        ];
    }*/

    public function getCapacityByTranche()
    {
        $evenementActif = Evenement::where('actif', 1)->first();

        if (!$evenementActif) {
            return ['message' => 'Aucun évènement actif'];
        }

        $animations = DB::table('animations')
            ->where('evenement_id', $evenementActif->id)
            ->select('capacity', 'open_time')
            ->get();

        $tranchesParJour = [];

        foreach ($animations as $anim) {
            $date = \Carbon\Carbon::parse($anim->open_time)->format('Y-m-d');
            $hour = \Carbon\Carbon::parse($anim->open_time)->hour;

            if ($hour >= 8 && $hour < 14) $tranche = 'Matin';
            elseif ($hour >= 14 && $hour < 19) $tranche = 'Après-midi';
            elseif ($hour >= 19) $tranche = 'Soir';
            else continue;

            if (!isset($tranchesParJour[$date])) {
                $tranchesParJour[$date] = [
                    'Matin' => ['capacity' => 0],
                    'Après-midi' => ['capacity' => 0],
                    'Soir' => ['capacity' => 0],
                ];
            }

            $tranchesParJour[$date][$tranche]['capacity'] += $anim->capacity+1;
        }

        // Nombre total de participants sur l'événement
        $totalParticipants = DB::table('evenement_users')
            ->where('evenement_id', $evenementActif->id)
            ->count();

        // Ajout participants et calcul missing/surplus par jour/tranche
        foreach ($tranchesParJour as $date => $tranches) {
            foreach ($tranches as $key => $t) {
                $tranchesParJour[$date][$key]['participants'] = $totalParticipants;
                $tranchesParJour[$date][$key]['missing_places'] = max(0, $totalParticipants - $t['capacity']);
                $tranchesParJour[$date][$key]['surplus_places'] = max(0, $t['capacity'] - $totalParticipants);
            }
        }

        return [
            'evenement_id' => $evenementActif->id,
            'evenement_nom' => $evenementActif->name ?? 'Événement actif',
            'tranches_par_jour' => $tranchesParJour,
            'total_participants' => $totalParticipants,
        ];
    }


}