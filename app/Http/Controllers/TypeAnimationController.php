<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Type_animation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TypeAnimationController extends Controller
{

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : typeAnimation ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function getTypeAnimation() {
        // Récupère tous les types d'animations enregistrés dans la bdd
        Log::info("---INDEX DES TYPES D'ANIMATIONS---");
        $typeAnimation = Type_animation::select('id', 'type')->get();
        //Log::info($typeAnimation);
        // Récupere l'evenement actif pour limiter la conv
        $DatesEvent = Evenement::select('date_opening','date_ending')->where('actif', '=', '1')->first();
        //Log::info($DatesEvent);
        //Log::info(date('d/m/Y',strtotime($DatesEvent->date_opening)));
        return response()->json([
            'status' => 'true',
            'message' => 'Voici les types d\'animation / date event!',
            'typeAnimation' => $typeAnimation,
            'DatesEvent' => $DatesEvent,
            'open_day' => date('d/m/Y',strtotime($DatesEvent->date_opening)),
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Type_animation $type_animation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Type_animation $type_animation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Type_animation $type_animation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Type_animation $type_animation)
    {
        //
    }
}
