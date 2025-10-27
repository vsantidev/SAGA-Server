<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{

    public function getRoomAvailable(Request $request){
    //Log::info("getRoomAvailable");
    $dateDebut = $request->query('open_time');
    $dateFin = $request->query('closed_time');
    //Log::info($dateDebut);
    //Log::info($dateFin);
    if (!$dateDebut || !$dateFin) {
        return response()->json(['error' => 'Dates manquantes'], 400);
    }

    $rooms = Room::with(['animations' => function ($query) use ($dateDebut, $dateFin) {
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
    public function show(Room $room)
    {
        //
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
    public function update(Request $request, Room $room)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        //
    }
}
