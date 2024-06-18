<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationRegister ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function createRegister(Request $request, Int $id): JsonResponse
    {
        Log::info("---Controller Inscription : createRegister | connected---");
        $request->validate([
            'user_id' => 'required'
        ]);

        $userRegister = User::find($request->user_id);

        Log::info("---Controller Inscription : createRegister | userData---");

        $userData = [
            'id' => $userRegister->id,
            'lastname' => $userRegister->lastname,
            'firstname' => $userRegister->firstname
        ];
        Log::info($userData);

        Log::info("---Controller Inscription : createRegister | Table Inscription---");
        Log::info($id);
        $registerInscription = Inscription::firstOrNew([
            'user_id' => $request->user_id,
            'animation_id' => $id
        ]);
        $registerInscription->save();
        Log::info($registerInscription);


        Log::info("---Controller Inscription : createRegister | Create Inscription---");
        return response()->json([
            'status' => 'true',
            'message' => 'L\'utilisateur a été inscrit sur l\'animation !',
            // 'User profile : ' => $userData,
            'id' => $userRegister->id,
            'lastname' => $userRegister->lastname,
            'firstname' => $userRegister->firstname
        ]);
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
    public function show(Inscription $inscription)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inscription $inscription)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inscription $inscription)
    {
        //
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : UnsubscribeRegisterDestroy ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function destroyRegistration(Request $request)
    {
        Log::info("---Controller Inscripton : destroy Registration Animation | Connexion---");
        $request->validate([
            "animation_id" => "required|integer",
            "user_id" => "required|integer",
        ]);

        Log::info("---Controller Inscripton : destroy Registration Animation | Request 1---");
        Log::info($request);

        // $animationUnsubscribe = Inscription::('user_id',$request->user_id);
        $animationUnsubscribe = Inscription::where('animation_id', $request->animation_id)
        ->where('user_id', $request->user_id)
        ->delete();
        // $animationUnsubscribe->delete();

        Log::info("---Controller Inscripton : destroy Registration Animation | Request 2---");
        Log::info($request);

        return response()->json([
            'status' => 'true',
            'message' => 'Votre inscription à l\'animation a été supprimé !',
            'Inscription supprimée : ' => $request->id
        ]);
    }
}
