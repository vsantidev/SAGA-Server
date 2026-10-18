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

        if ($stats->isEmpty()) {
            return response()->json(['message' => 'Aucun événement actif trouvé.'], 404);
        }

        return response()->json($stats);
    }

    public function capacityByTranche()
    {
        return response()->json($this->stats->getCapacityByTranche());
    }
}