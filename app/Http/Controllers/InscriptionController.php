<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;
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
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : handleRegisterUser ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function createRegisterAdmin(Request $request, Int $id): JsonResponse
    {
        //Log::info("---Controller Inscription : createRegister | connected---");
        $request->validate([
            'user_id' => 'required'
        ]);

        $userRegister = User::find($request->user_id);

        //Log::info("---Controller Inscription : createRegister | userData---");

        $userData = [
            'id' => $userRegister->id,
            'lastname' => $userRegister->lastname,
            'firstname' => $userRegister->firstname
        ];
        //Log::info($userData);

        //Log::info("---Controller Inscription : createRegister | Table Inscription---");
        //Log::info($id);
        $registerInscription = Inscription::firstOrNew([
            'user_id' => $request->user_id,
            'animation_id' => $id
        ]);
        $registerInscription->save();
        //Log::info($registerInscription);

        Log::info("JOURNAL : ---Controller INSCRIPTION ADD : Inscription de l'user $userRegister->lastname $userRegister->firstname ds l'animation : $id ---");
        //Log::info("---Controller Inscription : createRegister | Create Inscription---");
        return response()->json([
            'status' => 'true',
            'message' => 'L\'utilisateur a été inscrit sur l\'animation !',
            // 'User profile : ' => $userData,
            'id' => $userRegister->id,
            'lastname' => $userRegister->lastname,
            'firstname' => $userRegister->firstname,
            'animation_id' => $id,
        ]);
    }


    public function createRegister(Request $request, Int $id): JsonResponse
    {
        //Log::info("---Controller Inscription : createRegister | connected---");
        $request->validate([
            'user_id' => 'required'
        ]);

        $userRegister = User::find($request->user_id);

        //Log::info("---Controller Inscription : createRegister | userData---");

        $userData = [
            'id' => $userRegister->id,
            'lastname' => $userRegister->lastname,
            'firstname' => $userRegister->firstname
        ];

        $result = DB::table('animations')
        ->leftJoin('inscriptions', 'animations.id', '=', 'inscriptions.animation_id')
        ->select('animations.capacity', DB::raw('COUNT(inscriptions.id) as total_inscriptions'))
        ->where('animations.id', $id)
        ->groupBy('animations.id', 'animations.capacity') // Ajoute 'animations.id' pour éviter des erreurs de regroupement
        ->first();

  
        //Log::info("TOTAL INSCRIPTION $result->total_inscriptions");
        //Log::info("CAPACITY $result->capacity");

        if (($result && $result->total_inscriptions >= $result->capacity) || !$result) {
        //Log::info("JOURNAL : CAPA MAX atteinte");

        return response()->json([
            'status' => 'true',
            'message' => 'Il n\'y a plus de place sur cette animation!',
            'animation_id' => $id,
        ]);
        }else
        {
            $registerInscription = Inscription::firstOrNew([
                'user_id' => $request->user_id,
                'animation_id' => $id
            ]);
            $registerInscription->save();
    
            Log::info("JOURNAL : ---Controller INSCRIPTION ADD : Inscription de l'user $userRegister->lastname $userRegister->firstname ds l'animation : $id ---");
            //Log::info("---Controller Inscription : createRegister | Create Inscription---");
            return response()->json([
                'status' => 'true',
                'message' => 'L\'utilisateur a été inscrit sur l\'animation !',
                'id' => $userRegister->id,
                'lastname' => $userRegister->lastname,
                'firstname' => $userRegister->firstname,
                'animation_id' => $id,
            ]);
        }

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
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION SHOW : UnsubscribeRegisterDestroy ~~~~~~~~~~~~~~~~~~~~~~~~~~
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

        // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION SHOW (ADMIN) : UnsubscribeRegisterDestroy ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function destroyRegistrationAdmin(Request $request)
    {
        Log::info("---Controller Inscripton : destroy Registration AnimationShow (admin) | Connexion---");
        $request->validate([
            "animation_id" => "required|integer",
            "user_id" => "required|integer",
            // "id" => "required|integer",
        ]);

        Log::info("---Controller Inscripton : destroy Registration AnimationShow (admin) | Request 1---");
        Log::info($request);

        // $animationUnsubscribe = Inscription::('user_id',$request->user_id);
        $animationUnsubscribe = Inscription::where('animation_id', $request->animation_id)
        ->where('user_id', $request->user_id)
        // ->where('id', $id)
        ->delete();
        // $animationUnsubscribe->delete();

        Log::info("---Controller Inscripton : destroy Registration AnimationShow (admin) | Request 2---");
        Log::info($request);

        return response()->json([
            'status' => 'true',
            'message' => 'Ce membre a été désinscrit à l\'animation !',
            'Inscription supprimée : ' => $request->animation_id
        ]);
    }
}
