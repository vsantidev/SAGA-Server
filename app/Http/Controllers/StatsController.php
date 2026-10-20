<?php

namespace App\Http\Controllers;

use App\Services\StatsService;

class StatsController extends Controller
{
    protected $stats;

    public function __construct(StatsService $stats)
    {
        $this->stats = $stats;
    }

    public function animations()
    {
        $stats = $this->stats->getRawAnimationsStats();

        // Pas d'animation validée (ou pas de convention active) : ce n'est pas une erreur,
        // l'écran affiche le message au lieu d'une erreur générique
        if ($stats->isEmpty()) {
            return response()->json(['message' => 'Aucune animation validée pour la convention active.']);
        }

        return response()->json($stats);
    }

    public function capacityByTranche()
    {
        return response()->json($this->stats->getCapacityByTranche());
    }
}