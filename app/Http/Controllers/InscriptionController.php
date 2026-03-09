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
        $request->validate([
            'user_id' => 'required',
            'weight'  => 'required|integer|min:1|max:10'
        ]);

        $userRegister = User::findOrFail($request->user_id);

        // Vérifier que l'user n'a pas déjà un vœu sur cette animation
        $alreadyRegistered = Inscription::where('user_id', $request->user_id)
            ->where('animation_id', $id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json([
                'status'  => 'false',
                'message' => 'Vous etes deja inscrit sur cette animation',
            ], 409);
        }

        // Créer le vœu
        $inscription = Inscription::create([
            'user_id'       => $request->user_id,
            'animation_id'  => $id,
            'weight'        => $request->weight,
            'status'        => 'pending',
            'registered_at' => now(),
        ]);

        Log::info("REGISTER: User {$userRegister->lastname} {$userRegister->firstname} registered wish (weight: {$request->weight}) for animation {$id}");

        return response()->json([
            'status'       => 'true',
            'message'      => 'Your wish has been registered!',
            'id'           => $userRegister->id,
            'lastname'     => $userRegister->lastname,
            'firstname'    => $userRegister->firstname,
            'animation_id' => $id,
            'weight'       => $request->weight,
        ], 201);
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

    // Get priorities for the slot
    public function getUserPrioritiesBySlot(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        $usedPriorities = Inscription::where('user_id', $request->user_id)
            ->whereHas('animation', fn($q) =>
                $q->where('time_slot_id', $request->time_slot_id)
            )
            ->pluck('weight');

        return response()->json([
            'status' => 'true',
            'used_priorities' => $usedPriorities,
        ]);
    }

    public function getUserInscription(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'animation_id' => 'required|exists:animations,id',
        ]);

        $inscription = Inscription::where('user_id', $request->user_id)
            ->where('animation_id', $request->animation_id)
            ->first();

        return response()->json([
            'status'      => 'true',
            'inscription' => $inscription,
        ]);
    }
}
