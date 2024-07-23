<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class SponsorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function sponsorsIndex(Request $request)
    {
        Log::info("---SPONSOR CONTROLLER : Function Index ---");

        //Récupération de la liste des sponsors
        $sponsor=Sponsor::select('id', 'name', 'content', 'link','picture',)->get();
        
        return response()->json([
            'status' => 'true',
            'message' => 'Affichage des sponsors',
            'listeSponsors' => $sponsor ,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Log::info("---SPONSOR CONTROLLER : Function Create ---");
        Log::info("---SPONSOR CREATE : Request---");
        Log::info($request);

        Log::info("---SPONSOR CREATE : Picture---");
        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension= $file->getClientOriginalExtension();
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/sponsors/'), $filename);
        }else{
            $filename = 'img_default.jpg';
        }

        Log::info("---SPONSOR CREATE : Create---");
        $sponsorCreate = Sponsor::create([
            'name' => $request->name,
            'content' => $request->content,
            'link' => $request->link,
            'picture' => "images/sponsors/$filename"
        ]);

        Log::info("---SPONSOR CREATE : sponsorCreate avant JSON---");
        Log::info($sponsorCreate);

        return response()->json([
            'sponsor' => $sponsorCreate,
            'message' => 'Création du partenaire réussi !'
        ], 201);

        Log::info("---SPONSOR CREATE : sponsorCreate après JSON---");
        Log::info($sponsorCreate);

        
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
    public function show(Sponsor $sponsor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Int $id): JsonResponse
    {
        Log::info("---Sponsor Controller (Update | Request 1) ---");

        $sponsorData = Sponsor::find($id);

        Log::info($sponsorData);

        return response()->json([
            'status' => 'true',
            'message' => 'Voici le détail du partenaire !',
            'sponsorData' => $sponsorData
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Int $id): JsonResponse
    {
        Log::info("---Sponsor Controller (Update | Request 1) ---");
        Log::info($request);

        $sponsorId = Sponsor::find($id);

        $request->validate([
            'name' => 'required',
            'content' => 'required'
        ]);

        Log::info("---Sponsor Controller (Update | Request 2) ---");
        Log::info($request);


        Log::info("---Sponsor Controller (Update | Request 2) ---");
        $sponsorUpdate = Sponsor::findOrFail($request->id);
        $sponsorUpdate->name = $request->name;
        $sponsorUpdate->content = $request->content;
        $sponsorUpdate->link = $request->link;
        $sponsorUpdate->picture = $request->picture;
        $sponsorUpdate->save;
        Log::info($sponsorUpdate);

        return response()->json([
            'status' => 'true',
            'message' => 'Le partenaire a été mise à jour avec succès',
            'sponsor' => $sponsorUpdate,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sponsor $sponsor)
    {
        //
    }
}
