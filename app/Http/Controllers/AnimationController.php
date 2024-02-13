<?php

namespace App\Http\Controllers;

use App\Models\Animation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnimationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function animationIndex()
    {
        Log::info("--- ANIMATION INDEX ---");
        return Animation::select('id', 'title', 'content')->get();

        // Récupère les type d'animations associés à l'ID de la table animations
        // $type_animations = DB::table('Type_animations')->where('type_animations.animations_id', $id)->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Affichage des animations !'
            // 'type_animations' => $type_animations,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function animationCreate(Request $request)
    {
        Log::info("---ANIMATION CONTROLLER : Function AnimationCreate ---");

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required'
        ]);

        // -> Récupère le user par son ID
        // $user_id = User::findOrFail($id);
        // $user_id = Auth::id();

        Log::info("---ANIMATION CREATE : Request---");
        Log::info($request);

        Log::info("---ANIMATION CREATE : User_id---");
        // Log::info($user_id);

        $animationCreate = Animation::create([
            'title' => $request->title,
            'content' => $request->content,
            'validate' => $request->validate,
            'user_id' => $request->user_id
        ]);

        Log::info("---ANIMATION CREATE : AnimationCreate avant json---");
        Log::info($animationCreate);

        return response()->json([
            'animation' => $animationCreate,
            'message' => 'Création du formulaire d\'animation réussi !'
        ], 201);

        Log::info("---ANIMATION CREATE : AnimationCreate après json---");
        Log::info($animationCreate);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function animationStore(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function animationShow(Animation $animation, Int $id)
    {   
        // Récupère les type d'animations associés à l'ID de la table animations
        // $type_animations = DB::table('Type_animations')->where('type_animations.animations_id', $id)->get();

        // return response()->json([
        //     'type_animations' => $type_animations
        // ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function animationEdit(Animation $animation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function animationUpdate(Request $request, Animation $animation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function animationDestroy(Animation $animation)
    {
        //
    }
}
