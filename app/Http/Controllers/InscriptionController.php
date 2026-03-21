<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\User;
use App\Models\Timeslot;
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

        $userRegister = User::findOrFail($request->user_id);

        // Vérifier que l'user n'a pas déjà un vœu sur cette animation
        $alreadyRegistered = Inscription::where('user_id', $request->user_id)
            ->where('animation_id', $id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json([
                'status'  => 'false',
                'message' => 'Utilisateur déja inscrit sur cette animation',
            ], 409);
        }

        // Créer le vœu
        $inscription = Inscription::create([
            'user_id'       => $request->user_id,
            'animation_id'  => $id,
            'weight'        => '0',
            'status'        => 'pending',
            'registered_at' => now(),
        ]);

        Log::info("REGISTER: User {$userRegister->lastname} {$userRegister->firstname} registered wish (weight: {$request->weight}) for animation {$id}");

        return response()->json([
            'status'       => 'true',
            'message'      => 'inscription admin done!',
            'id'           => $userRegister->id,
            'lastname'     => $userRegister->lastname,
            'firstname'    => $userRegister->firstname,
            'animation_id' => $id,
            'weight'       => '0',
        ], 201);
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

    //Recup toutes les animations d'un user
    public function getUserInscriptions(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $inscriptions = Inscription::where('user_id', $request->user_id)
            ->whereHas('animations', fn($q) =>
                $q->whereHas('evenements', fn($q2) =>
                    $q2->where('actif', 1)
                )
            )
            ->with([
                'animations' => fn($q) => $q
                    ->select('animations.id', 'title', 'open_time', 'closed_time', 'capacity', 'min_capacity', 'time_slot_id', 'user_id', 'room_id')
                    ->whereHas('evenements', fn($q2) => $q2->where('actif', 1))
                    ->with([
                        'timeSlot:id,name,start_time,end_time',
                        'rooms:id,name',        // ← relation directe
                        'user:id,firstname,lastname', // ← attention c'est belongsTo donc user singulier si tu renommes
                    ]),
            ])
            ->get()
            ->map(fn($inscription) => [
                'inscription_id' => $inscription->id,
                'weight'         => $inscription->weight,
                'status'         => $inscription->status,
                'animation' => $inscription->animations ? [
                    'id'          => $inscription->animations->id,
                    'title'       => $inscription->animations->title,
                    'open_time'   => $inscription->animations->open_time,
                    'closed_time' => $inscription->animations->closed_time,
                    'capacity'    => $inscription->animations->capacity,
                    'room_name'   => $inscription->animations->rooms?->name ?? '',
                    'animator' => $inscription->animations->user
                        ? ucfirst(strtolower($inscription->animations->user->firstname)) . ' ' . strtoupper($inscription->animations->user->lastname)
                        : '',
                ] : null,
                'time_slot' => $inscription->animations?->timeSlot?->id ? [
                    'id'       => $inscription->animations->timeSlot->id,
                    'name'     => $inscription->animations->timeSlot->name,
                    'open_time'=> $inscription->animations->timeSlot->start_time,
                    'end_time' => $inscription->animations->timeSlot->end_time,
                ] : null,
            ])
            ->filter(fn($item) => $item['animation'] !== null)
            ->values();

        return response()->json([
            'status'       => 'true',
            'inscriptions' => $inscriptions,
        ]);
    }

    // =================================================================================
    // Get priorities for the slot
    public function getUserPrioritiesBySlot(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        $usedPriorities = Inscription::where('user_id', $request->user_id)
            ->whereHas('animations', fn($q) =>
                $q->where('time_slot_id', $request->time_slot_id)
            )
            ->pluck('weight');

        Log::info("---Controller Inscripton : prio user | Request 1---");
        Log::info($usedPriorities);
            
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


    public function getRegistrationStatus(Request $request): JsonResponse
    {
        Log::info($request);
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'animation_id' => 'required|exists:animations,id',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        // 1. Inscription de l'user sur cette animation
        $inscription = Inscription::where('user_id', $request->user_id)
            ->where('animation_id', $request->animation_id)
            ->first();

        Log::info($inscription);
        // 2. Priorités déjà utilisées sur ce créneau
        $usedPriorities = Inscription::where('user_id', $request->user_id)
            ->whereHas('animations', fn($q) =>
                $q->where('time_slot_id', $request->time_slot_id)
            )
            ->pluck('weight');

        Log::info($usedPriorities);
        // 3. Statut du créneau + places restantes
        $timeSlot = TimeSlot::with('animations')->findOrFail($request->time_slot_id);
        $placesLeft = $timeSlot->animations->sum(function ($animation) {
            $confirmed = $animation->inscriptions()->where('status', 'confirmed')->count();
            return max(0, $animation->capacity - $confirmed);
        });
        Log::info($placesLeft);

        return response()->json([
            'status'          => 'true',
            'inscription'     => $inscription,
            'used_priorities' => $usedPriorities,
            'draw_status'     => $timeSlot->draw_status,
            'places_left'     => $timeSlot->draw_status === 'drawn' ? $placesLeft : null,
        ]);
    }
}
