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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

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

        $this->validateTimes(
            Evenement::findOrFail($request->evenement_id),
            $request->start_time,
            $request->end_time
        );

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
        // Modification des créneaux réservée aux admins
        if ($request->user()->type !== 'admin') {
            return response()->json([
                'status'  => 'false',
                'message' => 'Réservé aux admins.',
            ], 403);
        }

        $timeSlot = TimeSlot::findOrFail($id);

        // drawn n'est posé que par le calcul des inscriptions (draw)
        $request->validate([
            'name'        => 'nullable|string|max:255',
            'start_time'  => 'sometimes|date',
            'end_time'    => 'nullable|date',
            'draw_status' => 'sometimes|in:open,closed',
        ], [
            'draw_status.in' => 'Le statut ne peut être que ouvert ou fermé : le passage à « calculé » se fait par le calcul des inscriptions.',
        ]);

        // Empêcher de modifier un créneau dont le tirage est déjà fait
        if ($timeSlot->draw_status === 'drawn') {
            return response()->json([
                'status'  => 'false',
                'message' => 'Cannot modify a time slot that has already been drawn!',
            ], 403);
        }

        // Horaires (seulement s'ils sont envoyés : ouvrir / fermer ne les touche pas)
        if ($request->exists('start_time') || $request->exists('end_time')) {
            // On valide la plage obtenue après fusion avec les valeurs en base
            $start = $request->input('start_time', $timeSlot->start_time?->format('Y-m-d H:i:s'));
            $end   = $request->exists('end_time')
                ? $request->input('end_time')
                : $timeSlot->end_time?->format('Y-m-d H:i:s');
            $this->validateTimes($timeSlot->evenement, $start, $end);

            // Pas de déplacement d'un créneau qui a des animations : elles garderaient l'ancienne plage
            $moved = !$this->sameTime($start, $timeSlot->start_time)
                || !$this->sameTime($end, $timeSlot->end_time);
            if ($moved && $timeSlot->animations()->exists()) {
                return response()->json([
                    'status'  => 'false',
                    'message' => "Impossible de changer les horaires d'un créneau qui a des animations.",
                ], 422);
            }
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

    // Plage d'un créneau : fin après le début, le tout dans les dates de la convention
    private function validateTimes(Evenement $evenement, $start, $end): void
    {
        Validator::make(
            ['start_time' => $start, 'end_time' => $end],
            [
                'start_time' => ['required', 'date', 'after_or_equal:' . $evenement->date_opening, 'before:' . $evenement->date_ending],
                'end_time'   => ['nullable', 'date', 'after:start_time', 'before_or_equal:' . $evenement->date_ending],
            ],
            [
                'start_time.after_or_equal' => 'Le créneau doit commencer pendant la convention.',
                'start_time.before'         => 'Le créneau doit commencer pendant la convention.',
                'end_time.after'            => 'La fin du créneau doit être après son début.',
                'end_time.before_or_equal'  => 'Le créneau doit finir au plus tard à la fin de la convention.',
            ]
        )->validate();
    }

    private function sameTime($value, ?Carbon $current): bool
    {
        if ($value === null || $value === '' || $current === null) {
            return ($value === null || $value === '') && $current === null;
        }

        return Carbon::parse($value)->equalTo($current);
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
                $capacity = $inscription->animations->capacity ?? 0;
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