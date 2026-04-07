<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{

    public function getRoomAvailable(Request $request)
    {
        //Log::info("getRoomAvailable");
        $dateDebut = $request->query('open_time');
        $dateFin = $request->query('closed_time');
        //Log::info($dateDebut);
        //Log::info($dateFin);
        if (!$dateDebut || !$dateFin) {
            return response()->json(['error' => 'Dates manquantes'], 400);
        }

        $rooms = Room::where('active', 1) 
        ->with(['animations' => function ($query) use ($dateDebut, $dateFin) {
            $query->where('validate', true)
                ->where(function ($q) use ($dateDebut, $dateFin) {
                    $q->where(function ($q2) use ($dateDebut, $dateFin) {
                        $q2->where('open_time', '<', $dateFin)  // strictement <
                            ->where('closed_time', '>', $dateDebut); // strictement >
                    });
                });
        }])->get();


        $rooms = $rooms->map(function ($room) {
            return [
                'id' => $room->id,
                'nom' => $room->name,
                'is_taken' => $room->animations->isNotEmpty(),
            ];
        });

        return response()->json($rooms);
    }


    public function getAllRooms(): JsonResponse
    {
        $rooms = Room::select('id', 'name', 'building', 'floor', 'capacity', 'pmr', 'active', 'picture')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'true',
            'rooms'  => $rooms,
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────
    public function show(Int $id): JsonResponse
    {
        $room = Room::findOrFail((int)$id);

        return response()->json([
            'status' => 'true',
            'room'   => $room,
        ]);
    }

    // ── CREATE ────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'building'    => 'nullable|string|max:255',
            'capacity'    => 'nullable|integer|min:1',
            'floor'       => 'nullable|string|max:50',
            'pmr'         => 'nullable|boolean',
            'description' => 'nullable|string',
            'active'      => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name', 'building', 'capacity',
            'floor', 'pmr', 'description', 'active',
        ]);

        // ── gestion image ──
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');

            // Vérifie que l'upload s'est bien passé
            if (!$file->isValid()) {
                return response()->json([
                    'status'  => 'false',
                    'message' => 'Erreur upload image',
                    'error'   => $file->getErrorMessage(),
                    'code'    => $file->getError(), // 1 = trop lourd, 2 = trop lourd (form), 3 = partiel, etc.
                ], 422);
            }

            try {
                $filename = uniqid() . "_" . $file->getClientOriginalName();
                $file->move(public_path('images/rooms/'), $filename);
                $data['picture'] = 'images/rooms/' . $filename;
            } catch (\Exception $e) {
                Log::error("ROOM CREATE: Erreur déplacement image — " . $e->getMessage());
                return response()->json([
                    'status'  => 'false',
                    'message' => 'Impossible de sauvegarder l\'image',
                    'error'   => $e->getMessage(),
                ], 500);
            }
        }

        $room = Room::create($data);
        Log::info("ROOM CREATE: Room {$room->name} created");

        return response()->json([
            'status'  => 'true',
            'message' => 'Salle créée avec succès',
            'room'    => $room,
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Int $id): JsonResponse
    {
        //Log::info($request);

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'building'    => 'sometimes|nullable|string|max:255',
            'capacity'    => 'sometimes|nullable|integer|min:1',
            'floor'       => 'sometimes|nullable|string|max:50',
            'pmr'         => 'sometimes|nullable|boolean',
            'description' => 'sometimes|nullable|string|nullable',
            'active'      => 'sometimes|nullable|boolean',
        ]);

        $room = Room::findOrFail((int)$id);
        $roomData = json_decode($request->input('room'), true);

        $data = [
            'name'        => $roomData['name']        ?? null,
            'building'    => $roomData['building']    ?? null,
            'capacity'    => $roomData['capacity']    ?? null,
            'floor'       => $roomData['floor']       ?? null,
            'pmr'         => $roomData['pmr']         ?? null,
            'description' => $roomData['description'] ?? null,
            'active'      => $roomData['active']      ?? null,
        ];

        // ── gestion image ──
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');

            if (!$file->isValid()) {
                return response()->json([
                    'status'  => 'false',
                    'message' => 'Erreur upload image',
                    'error'   => $file->getErrorMessage(),
                    'code'    => $file->getError(),
                ], 422);
            }

            try {
                // Supprime l'ancienne image si elle existe
                if ($room->picture && file_exists(public_path($room->picture))) {
                    unlink(public_path($room->picture));
                }

                $filename = uniqid() . "_" . $file->getClientOriginalName();
                $file->move(public_path('images/rooms/'), $filename);
                $data['picture'] = 'images/rooms/' . $filename;

            } catch (\Exception $e) {
                Log::error("ROOM UPDATE: Erreur déplacement image — " . $e->getMessage());
                return response()->json([
                    'status'  => 'false',
                    'message' => 'Impossible de sauvegarder l\'image',
                    'error'   => $e->getMessage(),
                ], 500);
            }
        }

        $room->update($data);
        Log::info("ROOM UPDATE: Room {$room->name} updated");

        return response()->json([
            'status'  => 'true',
            'message' => 'Salle mise à jour avec succès',
            'room'    => $room,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Int $id): JsonResponse
    {
        $room = Room::findOrFail($id);

        // ── supprime l'image si elle existe ──
        if ($room->picture && file_exists(public_path($room->picture))) {
            unlink(public_path($room->picture));
        }

        $room->delete();

        Log::info("ROOM DELETE: Room {$room->name} deleted");

        return response()->json([
            'status'  => 'true',
            'message' => 'Salle supprimée avec succès',
        ]);
    }
}
