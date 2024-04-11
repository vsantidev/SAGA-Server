<?php

namespace App\Http\Controllers;

use App\Models\Type_animation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TypeAnimationController extends Controller
{

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : typeAnimation ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function indexTypeAnimation() {
        // Récupère tous les users enregistrés dans la bdd
        Log::info("---INDEX DES TYPES D'ANIMTIONS---");
        return Type_animation::select('id', 'type')->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Voici les types d\'animation !',
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
