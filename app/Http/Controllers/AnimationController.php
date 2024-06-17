<?php

namespace App\Http\Controllers;

use App\Models\Animation;
use App\Models\Inscription;
use App\Models\User;
use App\Models\Type_animation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnimationController extends Controller
{

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationIndex()
    {
        Log::info("--- ANIMATION INDEX ---");
        return Animation::select('id', 'title', 'content', 'type_animation', 'open_time', 'closed_time', 'roleplay', 'reflection', 'fight', 'picture', 'capacity')->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Affichage des animations !'
        ]);

    }

    // =================================================================================
    
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationCreate ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationCreate(Request $request)
    {

        
        Log::info("---ANIMATION CONTROLLER : Function AnimationCreate ---");


        Log::info("---ANIMATION CREATE : Request---");
        Log::info($request);

        Log::info("---ANIMATION CREATE : debut picture---");

        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }else{
            $filename = 'img_default.jpg';
        }

        Log::info("---ANIMATION CREATE : fin picture---");
        $animationCreate = Animation::create([
            'title' => $request->title,
            'content' => $request->content,
            'validate' => $request->validate,
            'fight' => $request->fight,
            'reflection' => $request->reflection,
            'roleplay' => $request->roleplay,
            'open_time' => $request->open_time,
            'closed_time' => $request->closed_time,
            'evenement_id' => '1', // phase 1, en phase 2 affectation de l'evenement actif
            'type_animation_id' => $request->type_animation_id,
            'user_id' => $request->user_id,
            'picture'=> "images/$filename"
        ]);
		

        Log::info("---ANIMATION CREATE : AnimationCreate avant json---");
        Log::info($animationCreate);

        return response()->json([
            'animation' => $animationCreate,
            'message' => 'Création du formulaire d\'animation réussi !'
        ], 201);

        Log::info("---ANIMATION CREATE : AnimationCreate après json---");
        Log::info($animationCreate);
    }





    
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationRegisterStore ~~~~~~~~~~~~~~~~~~~~~~~~~~
    // public function animationRegisterStore(Request $request, Int $id): JsonResponse
    // {

    // }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationShow ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationShow(Request $request, Int $id): JsonResponse
    {   
        Log::info("---Function : AnimationShow connected---");
        //  1 - ~~~~~ Retrouver l'animation par id + récupérer les users dans Inscription par animation_id
        $animationShow = Animation::find($id);
        $inscriptionsTable = Inscription::select('user_id')->where('animation_id', '=', $id)->get();
        Log::info($inscriptionsTable);

        //  2 - ~~~~~ Récupérer les userInscrits dans un table et les rajouter
        Log::info("--- Function : AnimationShow --- Affichage de chaque userInscrits");
        $listUser= new Collection();

        foreach ($inscriptionsTable as $inscriptionsTables) {
            // ~~~~~ Récupère le users grâce a son ID
            $usersInscrit = User::select('id', 'firstname','lastname')->where('id',$inscriptionsTables->user_id)->get();
            Log::info("afffichage de chaque userinscrits");
            Log::info($usersInscrit);
            $listUser->push($usersInscrit);
        }
        Log::info($listUser);
        $type_animation = Type_animation::find($animationShow->type_animation_id);

        Log::info('type_animation');
        Log::info($type_animation);
        Log::info("---Function : AnimationShow Data => ---");
        $animationData = [
            'id' => $animationShow->id,
            'title' => $animationShow->title,
            'content' => $animationShow->content,
            'picture' => $animationShow->picture,
            'capacity' => $animationShow->capacity,
            'fight' => $animationShow->fight,
            'reflection' => $animationShow->reflection,
            'roleplay' => $animationShow->roleplay,
            'type_animation' => $type_animation->type,
            'user_id' => $animationShow->user_id,
            'open_time' => $animationShow->open_time,
            'closed_time' => $animationShow->closed_time
        ];
        Log::info($animationData);

        return response()->json([
            'status' => 'true',
            'message' => 'Voici le détail de l\'animation !',
            'listInscrits' => $listUser,
            'animationData' => $animationData
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function animationEdit(Animation $animation)
    {
        //
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationUpdate ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationUpdate(Request $request)
    {
        Log::info("---Function animationUpdate 1---");
        Log::info($request);

        $request->validate([
            'title' => 'required',
            'content' => 'required'
        ]);

        Log::info("---Function animationUpdate 2 ---");
        Log::info($request);

        // Récupère le lieu par son ID
        $animationUpdate = Animation::findOrFail($request->id);
        $animationUpdate->title = $request->title;
        $animationUpdate->content = $request->content;
        $animationUpdate->picture = $request->picture;
        $animationUpdate->capacity = $request->capacity;
        $animationUpdate->fight = $request->fight;
        $animationUpdate->reflection = $request->reflection;
        $animationUpdate->roleplay = $request->roleplay;
        $animationUpdate->type_animation = $request->type_animation;
        $animationUpdate->open_time = $request->open_time;
        $animationUpdate->closed_time = $request->closed_time;

        $animationUpdate->save();

        Log::info("---Function animationUpdate 3 ---");
        Log::info($animationUpdate);

        return response()->json([
            'status' => 'true',
            'message' => 'Animation mise à jour avec succès',
            'animation' => $animationUpdate,
        ]);

    }


    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationDestroy ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationDestroy(Request $request)
    {
        Log::info("---Function Animation : Destroy---");
        $request->validate([
            "id" => "required|integer",
        ]);

        Log::info($request);

        $animationDestroy = Animation::findOrFail($request->id);
        $animationDestroy->delete();
               
        return response()->json([
            'status' => 'true',
            'message' => 'L\'animation a été supprimé !',
            'Animation supprimée : ' => $request->id
        ]);
    }
}
