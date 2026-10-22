<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class SponsorController extends Controller
{
    // Règles communes à la création et à la mise à jour (colonnes de la table sponsors)
    private const RULES = [
        'name' => 'required|string|max:250',
        'content' => 'required|string',
        'link' => 'nullable|string|url|max:255',
    ];

    private const MESSAGES = [
        'name.required' => 'Merci de renseigner le nom du partenaire',
        'name.max' => 'Le nom ne doit pas dépasser 250 caractères',
        'content.required' => 'Merci de renseigner la présentation du partenaire',
        'link.url' => 'Le lien doit être une adresse complète, commençant par https://',
        'link.max' => 'Le lien ne doit pas dépasser 255 caractères',
    ];

    /**
     * Display a listing of the resource.
     */
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER SPONSOR : Index ~~~~~~~~~~~~~~~~~~~~~~~~~~
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

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER SPONSOR : Create ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //Log::info("---SPONSOR CONTROLLER : Function Create ---");
        //Log::info("---SPONSOR CREATE : Request---");
        //Log::info($request);

        // Champs vérifiés avant toute écriture (une présentation vide finissait en erreur 500)
        $request->validate(self::RULES, self::MESSAGES);

        //Log::info("---SPONSOR CREATE : Picture---");
        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension= $file->getClientOriginalExtension();
            $filename = $this->savePicture($file, 'sponsors');
        }else{
            $filename = 'img_default.jpg';
        }

        //Log::info("---SPONSOR CREATE : Create---");
        $sponsorCreate = Sponsor::create([
            'name' => $request->name,
            'content' => $request->content,
            'link' => $request->link,
            'picture' => "images/sponsors/$filename"
        ]);

        ///Log::info("---SPONSOR CREATE : sponsorCreate avant JSON---");
        //Log::info($sponsorCreate);
        Log::info("JOURNAL : ---Controller ANIMATION UPDATE : Création du sponsor : $sponsorCreate");

        return response()->json([
            'sponsor' => $sponsorCreate,
            'message' => 'Création du partenaire réussi !'
        ], 201);

        //Log::info("---SPONSOR CREATE : sponsorCreate après JSON---");
        //Log::info($sponsorCreate);

        
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


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER SPONSOR : EDIT ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Int $id): JsonResponse
    {
        //Log::info("---Sponsor Controller (Edit | Request 1) ---");

        $sponsorData = Sponsor::find($id);

        Log::info($sponsorData);

        return response()->json([
            'status' => 'true',
            'message' => 'Voici le détail du partenaire !',
            'sponsorData' => $sponsorData
        ]);
    }

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER SPONSOR : Update ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Int $id): JsonResponse
    {
        Log::info("---Sponsor Controller (Update | Request 1) ---");
        Log::info($request);

        // Mêmes règles qu'à la création, sur le partenaire envoyé en JSON
        $mySponsorRequest = json_decode($request->sponsor, true);
        Validator::make(
            is_array($mySponsorRequest) ? $mySponsorRequest : [],
            ['id' => 'required|integer'] + self::RULES,
            self::MESSAGES
        )->validate();

        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = $this->savePicture($file, 'sponsors');
            //$payload['picture']= 'public/images/'.$filename;
        }

        Log::info($mySponsorRequest);
        // Récupère l'utilisateur par son ID
        $sponsorUpdate = Sponsor::findOrFail($mySponsorRequest['id']);
        $sponsorUpdate->name = $mySponsorRequest['name'];
        $sponsorUpdate->content = $mySponsorRequest['content'];
        $sponsorUpdate->link = $mySponsorRequest['link'];
        if($request->hasFile('picture')){$sponsorUpdate->picture = "images/sponsors/$filename";}
        Log::info($sponsorUpdate);
        //Log::info("---Sponsor Controller (Update | Request 2 Save SQL) ---");
        //$sponsorUpdate->picture = $request->picture;
        
        $sponsorUpdate->save();
        //Log::info("aprés update");
        Log::info($sponsorUpdate);

        return response()->json([
            'status' => 'true',
            'message' => 'Le partenaire a été mis à jour avec succès',
            'sponsor' => $sponsorUpdate,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //Log::info("---Function Sponsor : Destroy---");
        $request->validate([
            "id" => "required|integer",
        ]);

        //Log::info($request);

        $sponsorDestroy = Sponsor::findOrFail($request->id);
        $sponsorDestroy->delete();
               
        return response()->json([
            'status' => 'true',
            'message' => 'Le partenaire a été supprimé !',
            'Sponsor supprimé : ' => $request->id
        ]);
    }
}
