<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use App\Models\Evenement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class TimeSlotController extends Controller
{
    // GET /time-slots?evenement_id=1
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'evenement_id' => 'required|exists:evenements,id'
        ]);

        $timeSlots = TimeSlot::where('evenement_id', $request->evenement_id)
            ->withCount('animations')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'status' => 'true',
            'data'   => $timeSlots
        ]);
    }

    // GET /time-slots/{id}
    public function show(Int $id): JsonResponse
    {
        $timeSlot = TimeSlot::with('animations')->findOrFail($id);

        // Calcul des places restantes sur le créneau (animations confirmed)
        $placesLeft = $timeSlot->animations->sum(function ($animation) {
            $confirmed = $animation->inscriptions()->where('status', 'confirmed')->count();
            return max(0, $animation->capacity - $confirmed);
        });

        return response()->json([
            'status' => 'true',
            'data'   => array_merge($timeSlot->toArray(), ['places_left' => $placesLeft])
        ]);
    }

    // POST /time-slots
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'evenement_id' => 'required|exists:evenements,id',
            'name'         => 'nullable|string|max:255',
            'start_time'   => 'required|date',
            'end_time'     => 'nullable|date|after:start_time',
        ]);

        $timeSlot = TimeSlot::create([
            'evenement_id' => $request->evenement_id,
            'name'         => $request->name,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'draw_status'  => 'open',
        ]);

        return response()->json([
            'status'  => 'true',
            'message' => 'Time slot created successfully!',
            'data'    => $timeSlot
        ], 201);
    }

    // PUT /time-slots/{id}
    public function update(Request $request, Int $id): JsonResponse
    {
        $timeSlot = TimeSlot::findOrFail($id);

        $request->validate([
            'name'        => 'nullable|string|max:255',
            'start_time'  => 'sometimes|date',
            'end_time'    => 'nullable|date|after:start_time',
            'draw_status' => 'sometimes|in:open,closed,drawn',
        ]);

        // Empêcher de modifier un créneau dont le tirage est déjà fait
        if ($timeSlot->draw_status === 'drawn') {
            return response()->json([
                'status'  => 'false',
                'message' => 'Cannot modify a time slot that has already been drawn!',
            ], 403);
        }

        $timeSlot->update($request->only([
            'name',
            'start_time',
            'end_time',
            'draw_status',
        ]));

        return response()->json([
            'status'  => 'true',
            'message' => 'Time slot updated successfully!',
            'data'    => $timeSlot
        ]);
    }

    // DELETE /time-slots/{id}
    public function destroy(Int $id): JsonResponse
    {
        $timeSlot = TimeSlot::findOrFail($id);

        // Empêcher la suppression si le tirage est déjà fait
        if ($timeSlot->draw_status === 'drawn') {
            return response()->json([
                'status'  => 'false',
                'message' => 'Cannot delete a time slot that has already been drawn!',
            ], 403);
        }

        // Empêcher la suppression si des animations sont liées
        if ($timeSlot->animations()->count() > 0) {
            return response()->json([
                'status'  => 'false',
                'message' => 'Cannot delete a time slot that has animations linked to it!',
            ], 403);
        }

        $timeSlot->delete();

        return response()->json([
            'status'  => 'true',
            'message' => 'Time slot deleted successfully!',
        ]);
    }

    // POST /time-slots/{id}/draw
    public function draw(Int $id): JsonResponse
    {
        $timeSlot = TimeSlot::with('animations')->findOrFail($id);

        if ($timeSlot->draw_status === 'drawn') {
            return response()->json([
                'status'  => 'false',
                'message' => 'Draw already done for this time slot!',
            ], 403);
        }

        // Tirage
        $inscriptions = $timeSlot->inscriptions()
            ->with('animations')
            ->orderBy('weight')
            ->orderBy('registered_at')
            ->get();

        $placesLeft  = [];  // animation_id => places restantes
        $usersPlaced = [];  // user_id => true

        foreach ($inscriptions as $inscription) {
            // User déjà placé sur ce créneau
            if (isset($usersPlaced[$inscription->user_id])) {
                $inscription->update(['status' => 'cascade_cancelled']);
                continue;
            }

            // Init compteur places
            if (!isset($placesLeft[$inscription->animation_id])) {
                $capacity = $inscription->animations->capacity ?? 0;  // ← animations au lieu de animation
                $placesLeft[$inscription->animation_id] = $capacity;
            }

            if ($placesLeft[$inscription->animation_id] > 0) {
                $inscription->update(['status' => 'confirmed']);
                $placesLeft[$inscription->animation_id]--;
                $usersPlaced[$inscription->user_id] = true;
            } else {
                $inscription->update(['status' => 'rejected']);
            }
        }

        $timeSlot->update([
            'draw_status' => 'drawn',
            'drawn_at'    => now(),
        ]);

        return response()->json([
            'status'  => 'true',
            'message' => 'Draw completed successfully!',
            'data'    => [
                'confirmed'         => $timeSlot->inscriptions()->where('status', 'confirmed')->count(),
                'rejected'          => $timeSlot->inscriptions()->where('status', 'rejected')->count(),
                'cascade_cancelled' => $timeSlot->inscriptions()->where('status', 'cascade_cancelled')->count(),
            ]
        ]);
    }

    public function undraw(Int $id): JsonResponse
    {
        $timeSlot = TimeSlot::with('animations')->findOrFail($id);

        // Vérifier que le tirage a bien été effectué
        if ($timeSlot->draw_status !== 'drawn') {
            return response()->json([
                'status'  => 'false',
                'message' => 'No draw to undo for this time slot!',
            ], 403);
        }

        // Remettre toutes les inscriptions du créneau en pending
        $timeSlot->inscriptions()->update([
            'status' => 'pending',
        ]);

        // Remettre le slot en open
        $timeSlot->update([
            'draw_status' => 'open',
            'drawn_at'    => null,
        ]);

        return response()->json([
            'status'  => 'true',
            'message' => 'Draw successfully undone!',
        ]);
    }
}