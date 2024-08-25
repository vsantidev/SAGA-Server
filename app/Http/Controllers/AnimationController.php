<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Animation;
use App\Models\Room;
use App\Models\Inscription;
use App\Models\Like;
use App\Models\User;
use App\Models\Type_animation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnimationController extends Controller
{
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationIndex(Request $request)
    {
        Log::info("--- ANIMATION INDEX ---");

        $IdUser = $request->query('param1');
        Log::info("--- ANIMATION INDEX : IdUser ---");
        Log::info($IdUser);
        //Recuperation des animations
        $Animations=Animation::select('id', 'title', 'content', 'type_animation_id', 'open_time','picture', 'validate','user_id', 'registration_date', 'fight', 'roleplay', 'reflection')->get();
        // $Animations = Animation::find($id);

        //Recupération des likes
        $listeLike=Like::select('animation_id')->where('user_id', '=', $IdUser)->get();
        //ajout de la colonne like dans le tableau des animations
        $listeAnimationLike=$Animations->map(function($Animation){
            $Animation->like = "";
            $Animation->type_animation_name = "";
            $Animation->author_name ="";
        return $Animation;
        });

        Log::info($Animations);
        Log::info('--- AnimationController - INDEX | type_animation');
        $alltypeAnimation = Type_animation::select('id','type')->get();
        Log::info($alltypeAnimation);
        $ListeUser = User::select('id', 'firstname','lastname')->get();
        Log::info($ListeUser);
        foreach($listeAnimationLike as $anim)
        {
            foreach($alltypeAnimation as $type_anim){
                
                if($anim->type_animation_id == $type_anim->id)
                {
                    $anim->type_animation_name=$type_anim->type;
                }
            }

            foreach($listeLike as $like)
            {
                if($anim->id == $like->animation_id)
                {
                    $anim->like="1";
                }
            }
            foreach($ListeUser as $user){
                if($anim->user_id == $user->id)
                {
                    $anim->author_name= ucfirst(strtolower($user->firstname))." ".strtoupper($user->lastname);
                }
            }

        }

        return response()->json([
            'status' => 'true',
            'message' => 'Affichage des animations + bonus Like!',
            'listeAnimation' => $listeAnimationLike ,
            //'allTypeAnimations' => $alltypeAnimation,
        ]);

    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationListIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationListIndex()
    {
        Log::info("---Controller Animation : Index List Animation | Connexion---");
        return Animation::select('id', 'title', 'content', 'type_animation_id', 'open_time', 'closed_time', 'roleplay', 'reflection', 'fight', 'picture','room_id', 'capacity', 'validate')->get();
        return response()->json([
            'status' => 'true',
            'message' => 'AnimationListIndex : Affichage des animations !'
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
            $file->move(public_path('images/animations/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }else{
            $filename = 'img_default.jpg';
        }

        Log::info("---ANIMATION CREATE : fin picture---");

        $DatesEvent = Evenement::select('id','date_opening','date_ending')->where('actif', '=', '1')->first();
        //comparaison dates events
        Log::info("---ANIMATION CREATE : verif date---");
    
        if($request->open_time >= $DatesEvent->date_opening && $request->closed_time <= $DatesEvent->date_ending)
        {        
                $animationCreate = Animation::create([
                'registration' => $request->registration,
                'title' => $request->title,
                'content' => $request->content,
                'remark' => $request->remark,
                'multiple' => $request->multiple,
                'url' => $request->url,
                'validate' => $request->validate,
                'fight' => $request->fight,
                'reflection' => $request->reflection,
                'roleplay' => $request->roleplay,
                'open_time' => $request->open_time,
                'closed_time' => $request->closed_time,
                'time' => $request->time,
                'other_time' => $request->other_time,
                'capacity' => $request->capacity,
                'evenement_id' => $DatesEvent->id,
                'type_animation_id' => $request->type_animation_id,
                'user_id' => $request->user_id,
                'registration_date' => $request->registrationDate,
                'picture'=> "images/animations/$filename"
                
            ]);
            

            Log::info("---ANIMATION CREATE : AnimationCreate avant json---");
            Log::info($animationCreate);

            return response()->json([
                'animation' => $animationCreate,
                'message' => 'Création du formulaire d\'animation réussi !'
            ], 201);

            Log::info("---ANIMATION CREATE : AnimationCreate après json---");
            Log::info("JOURNAL : ---Controller ANIMATION CREATE: $request->user_id à crée l'animation $request->title ---");
        }else{
            return response()->json([
                'message' => 'Erreur dans l\'insertion!'
            ], 520);
        }
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : createValidation ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function createValidation(Request $request)
    {
        Log::info("---ANIMATION CONTROLLER : Function createValidation ---");
        $animationCreateValidation = Animation::create([
            'validate' => $request->validate,
            'type_animation_id' => $request->type_animation_id,
            'user_id' => $request->user_id,
        ]);

        if($animationCreateValidation->validate == "0")
        {
            $animationCreateValidation->validate = "1";
        }
        
        Log::info("---ANIMATION CREATE : Function createValidation avant json---");
        Log::info($animationCreateValidation);
        return response()->json([
            'animation' => $animationCreateValidation,
            'message' => 'Validation de l\'animation -> OK !'
        ], 201);
    }


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
        $alltypeAnimation = Type_animation::select('id', 'type')->get();
        Log::info($alltypeAnimation);
        $type_animation = Type_animation::find($animationShow->type_animation_id);

        Log::info('type_animation');

        Log::info($type_animation);

        //Recupération des salles si renseigné. -> uniquement SIEGRIES en V1
        if($animationShow->room_id != "")
        {
            $RoomAnim=Room::select('name','capacity')->where('id', '=', $animationShow->room_id)->first();
            //$animationShow->room_id = $RoomAnim->name;
            Log::info($animationShow->room_id);
            Log::info($RoomAnim->name);
            if ($animationShow->capacity == null)
            {
                $animationShow->capacity = $RoomAnim->capacity;
            }

            Log::info("---Function : AnimationShow Data Salle=> ---");
            $animationData = [
                'id' => $animationShow->id,
                'title' => $animationShow->title,
                'content' => $animationShow->content,
                'remark' => $animationShow->remark,
                'url' => $animationShow->url,
                'multiple' => $animationShow->multiple,
                'picture' => $animationShow->picture,
                'capacity' => $animationShow->capacity,
                'fight' => $animationShow->fight,
                'reflection' => $animationShow->reflection,
                'roleplay' => $animationShow->roleplay,
                'type_animation_id' => $type_animation->id,
                'type_animation_name' => $type_animation->type, 
                'user_id' => $animationShow->user_id,
                'validate' => $animationShow->validate,
                'open_time' => $animationShow->open_time,
                'closed_time' => $animationShow->closed_time,
                'time' => $animationShow->time,
                'other_time' => $animationShow->other_time,
                'registration' => $animationShow->registration,
                'registration_date' => $animationShow->registration_date,
                'room' => $RoomAnim->name,
                'room_id' => $animationShow->room_id,
            ];
        }else{
            Log::info("---Function : AnimationShow Data Sans Salle=> ---");
            $animationData = [
                'id' => $animationShow->id,
                'title' => $animationShow->title,
                'content' => $animationShow->content,
                'remark' => $animationShow->remark,
                'url' => $animationShow->url,
                'multiple' => $animationShow->multiple,
                'picture' => $animationShow->picture,
                'capacity' => $animationShow->capacity,
                'fight' => $animationShow->fight,
                'reflection' => $animationShow->reflection,
                'roleplay' => $animationShow->roleplay,
                'type_animation_id' => $type_animation->id,
                'type_animation_name' => $type_animation->type,
                'user_id' => $animationShow->user_id,
                'validate' => $animationShow->validate,
                'open_time' => $animationShow->open_time,
                'closed_time' => $animationShow->closed_time,
                'time' => $animationShow->time,
                'other_time' => $animationShow->other_time,
                'registration' => $animationShow->registration,
                'registration_date' => $animationShow->registration_date,
                //'room' => "",
                //'capacity' => "",
            ];
        }

        Log::info($animationData);

        //récupération de l'autheur
        $author = User::findOrFail($animationShow->user_id);

        return response()->json([
            'status' => 'true',
            'message' => 'Voici le détail de l\'animation !',
            'listInscrits' => $listUser,
            'animationData' => $animationData,
            'authorLastname' => $author->lastname,
            'authorFirstname'=> $author->firstname,
            'allTypeAnim' => $alltypeAnimation

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
        Log::info("---Controller Animation : update Animation |  Request 1---");
        Log::info($request);
        
        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/animations/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }

        Log::info("---Controller Animation : update Animation |  Request 2 ---");
        $myAnimationRequest = json_decode($request->animation, true); 
        Log::info($myAnimationRequest['url'].$myAnimationRequest['time']);

        // Récupère le lieu par son ID
        $animationUpdate = Animation::findOrFail($myAnimationRequest['id']);
        $animationUpdate->title = $myAnimationRequest['title'];
        $animationUpdate->content = $myAnimationRequest['content'];
        $animationUpdate->url = $myAnimationRequest['url'];
        if(isset($myAnimationRequest['registration_date'])){$animationUpdate->remark = $myAnimationRequest['remark'];}
        $animationUpdate->multiple = $myAnimationRequest['multiple'];
        $animationUpdate->picture = $myAnimationRequest['picture'];
        if(isset($myAnimationRequest['capacity'])){$animationUpdate->capacity = $myAnimationRequest['capacity'];}
        if(isset($myAnimationRequest['room_id'])){$animationUpdate->room_id = $myAnimationRequest['room_id'];}
        $animationUpdate->fight = $myAnimationRequest['fight'];
        $animationUpdate->reflection = $myAnimationRequest['reflection'];
        $animationUpdate->roleplay = $myAnimationRequest['roleplay'];
        $animationUpdate->type_animation_id = $myAnimationRequest['type_animation_id'];
        $animationUpdate->open_time = $myAnimationRequest['open_time'];
        $animationUpdate->closed_time = $myAnimationRequest['closed_time'];
        $animationUpdate->validate = $myAnimationRequest['validate'];
        $animationUpdate->time = $myAnimationRequest['time'];
        $animationUpdate->other_time = $myAnimationRequest['other_time'];
        $animationUpdate->registration = $myAnimationRequest['registration'];
        $animationUpdate->registration_date = $myAnimationRequest['registration_date'];
        if($request->hasFile('picture')){$animationUpdate->picture = "images/animations/$filename";}


        $animationUpdate->save();

        Log::info("---Controller Inscripton : update Animation |  Request 3---");
        Log::info($animationUpdate);

        $author = User::findOrFail($animationUpdate->user_id);

        if ($animationUpdate->validate == true && $author->type != "admin")
        {
            
            Log::info("---Controller Inscripton : update Animation |  verif author---");
            Log::info($author);
            Log::info($author->type);
            $author->type="animateur";
            Log::info($author->type);
            $author->save();
            

        }// on ne supprime pas le statut de l'animateur si il a déjà été validé une fois.
        // Sinon cela risque d'annuler pour des personnes qui ont déjà fait plusieurs animations.

        Log::info("JOURNAL : ---Controller ANIMATION UPDATE : modification de l'animation $request->title ---");

        return response()->json([
            'status' => 'true',
            'message' => 'Animation mise à jour avec succès',
            'animation' => $animationUpdate,
            'authorLastname' => $author->lastname,
            'authorFirstname'=> $author->firstname
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
