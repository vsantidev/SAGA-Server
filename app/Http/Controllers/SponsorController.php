<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
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
    public function edit(Sponsor $sponsor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sponsor $sponsor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sponsor $sponsor)
    {
        //
    }
}
