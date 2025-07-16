<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Evenement_user;
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
    //Récupération de l'ID de l'utilisateur
    $IdUser = $request->query('param1');

    $Animations = Animation::select(
            'animations.id',
            'animations.title',
            'animations.content',
            'animations.type_animation_id',
            'animations.open_time',
            'animations.closed_time',
            'animations.capacity',
            'animations.picture',
            'animations.validate',
            'animations.user_id',
            'animations.registration',
            'animations.registration_date',
            'animations.fight',
            'animations.roleplay',
            'animations.reflection',
            'type_animations.type as type_animation_name',
            DB::raw('CONCAT(UCASE(LEFT(users.firstname, 1)), LCASE(SUBSTRING(users.firstname, 2)), " ", UPPER(users.lastname)) as author_name'),
            DB::raw('COUNT(inscriptions.id) as nb_inscrits'),
            DB::raw('COUNT(inscriptions.id) as nb_inscrits'),
            DB::raw('(
                SELECT COUNT(*) 
                FROM evenement_users 
                WHERE user_id = animations.user_id 
                AND masters = true
            ) as medals')
        
        )
        ->leftJoin('type_animations', 'animations.type_animation_id', '=', 'type_animations.id')
        ->leftJoin('users', 'animations.user_id', '=', 'users.id')
        ->leftJoin('inscriptions', 'inscriptions.animation_id', '=', 'animations.id')
        ->join('evenements', 'animations.evenement_id', '=', 'evenements.id')
        ->where('evenements.actif', 1)
        ->groupBy('animations.id')
        ->orderBy('animations.open_time', 'asc')
        ->get();

    // Récupère les likes de l'utilisateur pour ajouter l'information de like
    $listeLike = Like::where('user_id', $IdUser)->pluck('animation_id')->toArray();

    // Applique les likes aux animations
    $Animations->map(function($animation) use ($listeLike) {
        $animation->like = in_array($animation->id, $listeLike) ? "1" : "0";
        return $animation;
    });

    //Log::info($Animations);

    return response()->json([
        'status' => 'true',
        'message' => 'Affichage des animations + bonus Like!',
        'listeAnimation' => $Animations,
    ]);
}

    /*public function animationIndex(Request $request)
    {
        //Log::info("--- ANIMATION INDEX ---");

        $IdUser = $request->query('param1');
        //Log::info("--- ANIMATION INDEX : IdUser ---");
        //Log::info($IdUser);
        //Recuperation des animations
        //$Animations=Animation::select('id', 'title', 'content', 'type_animation_id', 'open_time', 'closed_time', 'capacity' ,'picture', 'validate','user_id', 'registration' , 'registration_date', 'fight', 'roleplay', 'reflection')->get();
        // $Animations = Animation::find($id);
        $Animations = Animation::select('id', 'title', 'content', 'type_animation_id', 'open_time', 'closed_time', 'capacity', 'picture', 'validate', 'user_id', 'registration', 'registration_date', 'fight', 'roleplay', 'reflection')
        ->orderBy('open_time', 'asc') // 'asc' pour un ordre croissant, 'desc' pour un ordre décroissant
        ->get();
        //Recupération des likes de l'utilisateur en cours
        $listeLike=Like::select('animation_id')->where('user_id', '=', $IdUser)->get();
        //ajout de la colonne like dans le tableau des animations
        $listeAnimationLike=$Animations->map(function($Animation){
            $Animation->like = "0";
            $Animation->type_animation_name = "";
            $Animation->author_name ="";
            $Animation->nb_inscrits = 0;

        return $Animation;
        });

        $inscriptionsCount = Inscription::select('animation_id', DB::raw('count(*) as total'))
        ->groupBy('animation_id')
        ->get();

        //Log::info($inscriptionsCount);
        //Log::info($Animations);
        //Log::info('--- AnimationController - INDEX | type_animation');
        $alltypeAnimation = Type_animation::select('id','type')->get();
        //Log::info($alltypeAnimation);
        $ListeUser = User::select('id', 'firstname','lastname')->get();
        //Log::info($ListeUser);
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

            foreach($inscriptionsCount as $inscription){
                if($anim->id == $inscription->animation_id)
                {
                    $anim->nb_inscrits = $inscription->total;
                }
            }
        }

        return response()->json([
            'status' => 'true',
            'message' => 'Affichage des animations + bonus Like!',
            'listeAnimation' => $listeAnimationLike ,
            //'allTypeAnimations' => $alltypeAnimation,
        ]);

    }*/


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationListIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationListIndex()
    {
        //Log::info("---Controller Animation : Index List Animation | Connexion---");
        $Animations = Animation::select('id', 'title', 'content', 'type_animation_id', 'registration_date','open_time', 'closed_time', 'roleplay', 'reflection', 'fight', 'picture','room_id','user_id', 'capacity', 'validate')->get();          
       
        $alltypeAnimation = Type_animation::select('id','type')->get();
        $ListeUser = User::select('id', 'firstname','lastname')->get();
        $ListeRoom = Room::select('id', 'name')->get();
        $listeAnimationComplete=$Animations->map(function($Animation){
            $Animation->room_name = "";
            $Animation->type_animation_name = "";
            $Animation->author_name ="";
            $Animation->evenement_year ="";
        return $Animation;
        });

        foreach($listeAnimationComplete as $anim)
        {
            $anim->evenement_year = \Carbon\Carbon::parse($anim->open_time)->year;

            foreach($alltypeAnimation as $type_anim){
                
                if($anim->type_animation_id == $type_anim->id)
                {
                    $anim->type_animation_name=$type_anim->type;
                }
            }

            foreach($ListeUser as $user){
                if($anim->user_id == $user->id)
                {
                    $anim->author_name= ucfirst(strtolower($user->firstname))." ".strtoupper($user->lastname);
                }
            }

            foreach($ListeRoom as $room){
                if($anim->room_id == $room->id)
                {
                    $anim->room_name = $room->name;
                }
            }
        }
        //Log::info($listeAnimationComplete);
        return response()->json([
            'status' => 'true',
            'message' => 'AnimationListIndex : Affichage des animations !',
            'listeAnimation' => $listeAnimationComplete ,
        ]);
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationCreate ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationCreate(Request $request)
    {
        //Log::info("---ANIMATION CONTROLLER : Function AnimationCreate ---");
        Log::info("---ANIMATION CREATE : Request---");
        //Log::info($request);
        //Log::info("---ANIMATION CREATE : debut picture---");
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required',
            'fight' => 'required',
            'reflection' => 'required',
            'roleplay' => 'required',
            'open_time' => 'required|date',
            'closed_time' => 'required|date',
            'time' => 'required|integer|min:1',
            'capacity' => 'required',
            'type_animation_id' => 'required',
        ], [
            'titre.required' => 'Merci de renseigner un titre',
            'content.required' => 'Merci de renseigner une description',
            'fight.required' => 'Merci de renseigner une valeur d\'affrontement',
            'reflection.required' => 'Merci de renseigner de stratégie',
            'roleplay.required' => 'Merci de renseigner d\'ambiance',
            'open_time.required' => 'Merci de selectionner une tranche horaire',
            'closed_time.required' => 'Merci de renseigner une tranche horaire',
            'time.required' => 'Merci de renseigner la durée de l\'animation',
            'capacity.required' => 'Merci de renseigner le nombre de joueurs',
            'type_animation_id.required' => 'Merci de selectionner un type d\'animation',
        ]);
        
        Log::info("---ANIMATION CREATE : Fin validation---");

        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/animations/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }else{
            $filename = 'img_default_conv5.png';
        }

        //Log::info("---ANIMATION CREATE : fin picture---");

        $DatesEvent = Evenement::select('id','date_opening','date_ending')->where('actif', '=', '1')->first();
        //comparaison dates events
        //Log::info("---ANIMATION CREATE : verif date---");
    
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
            

            //Log::info("---ANIMATION CREATE : AnimationCreate avant json---");
            //Log::info($animationCreate);

            return response()->json([
                'animation' => $animationCreate,
                'message' => 'Création du formulaire d\'animation réussi !'
            ], 201);

            //Log::info("---ANIMATION CREATE : AnimationCreate après json---");
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
        //Log::info("---ANIMATION CONTROLLER : Function createValidation ---");
        $animationCreateValidation = Animation::create([
            'validate' => $request->validate,
            'type_animation_id' => $request->type_animation_id,
            'user_id' => $request->user_id,
        ]);

        if($animationCreateValidation->validate == "0")
        {
            $animationCreateValidation->validate = "1";
        }
        
        //Log::info("---ANIMATION CREATE : Function createValidation avant json---");
        //Log::info($animationCreateValidation);
        Log::info("JOURNAL : ---Controller ANIMATION VALIDATE: l'animation $animationCreateValidation a été validée) ---");
        return response()->json([
            'animation' => $animationCreateValidation,
            'message' => 'Validation de l\'animation -> OK !'
        ], 201);
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationShow ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animationShow(Request $request, Int $id): JsonResponse
    {   
        //Log::info("---Function : AnimationShow connected---");
        //  1 - ~~~~~ Retrouver l'animation par id + récupérer les users dans Inscription par animation_id
        $animationShow = Animation::find($id);
        $inscriptionsTable = Inscription::select('user_id')->where('animation_id', '=', $id)->get();
        //Log::info($inscriptionsTable);

        //  2 - ~~~~~ Récupérer les userInscrits dans un table et les rajouter
        //Log::info("--- Function : AnimationShow --- Affichage de chaque userInscrits");
        $listUser= new Collection();

        foreach ($inscriptionsTable as $inscriptionsTables) {
            // ~~~~~ Récupère le users grâce a son ID
            $usersInscrit = User::select('id', 'firstname','lastname')->where('id',$inscriptionsTables->user_id)->get();
            //Log::info("afffichage de chaque userinscrits");
            //Log::info($usersInscrit);
            $listUser->push($usersInscrit);
        }

        //Log::info($listUser);
        $alltypeAnimation = Type_animation::select('id', 'type')->get();
        //Log::info($alltypeAnimation);
        $type_animation = Type_animation::find($animationShow->type_animation_id);

        //Log::info('type_animation');

        //Log::info($type_animation);

        //Recupération des salles si renseigné. -> uniquement SIEGRIES en V1
        if($animationShow->room_id != "")
        {
            $RoomAnim=Room::select('name','capacity')->where('id', '=', $animationShow->room_id)->first();
            //$animationShow->room_id = $RoomAnim->name;
            //Log::info($animationShow->room_id);
            //Log::info($RoomAnim->name);
            if ($animationShow->capacity == null)
            {
                $animationShow->capacity = $RoomAnim->capacity;
            }

            //Log::info("---Function : AnimationShow Data Salle=> ---");
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
            //Log::info("---Function : AnimationShow Data Sans Salle=> ---");
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

        //Log::info($animationData);

        //récupération de l'autheur
        $author = User::findOrFail($animationShow->user_id);

        
        // Ajout du nombre de médailles
        $author->medals = DB::table('evenement_users')
        ->where('user_id', $author->id)
        ->where('masters', true)
        ->count();

        // Ajout des likes
        $userLikes = Like::where('animation_id', $animationShow->id)
                 ->pluck('user_id')
                 ->toArray();
        //Log::info("User Like");
        //Log::info($userLikes);


        return response()->json([
            'status' => 'true',
            'message' => 'Voici le détail de l\'animation !',
            'listInscrits' => $listUser,
            'animationData' => $animationData,
            'authorLastname' => $author->lastname,
            'authorFirstname'=> $author->firstname,
            'authorMedals'=> $author->medals,
            'allTypeAnim' => $alltypeAnimation,
            'userLikes'=> $userLikes

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
        //Log::info("---Controller Animation : update Animation |  Request 1---");
        //Log::info($request);
        
        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/animations/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }

        //Récupération de l'animation
        $myAnimationRequest = json_decode($request->animation, true);
        //Récupération de celui qui post
        $myUserRequest = json_decode($request->userUpdate, true);
        // Récupère le lieu par son ID
        $animationUpdate = Animation::findOrFail($myAnimationRequest['id']);

        Log::info($myUserRequest);
        $author = User::findOrFail($animationUpdate->user_id);
        


        
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
        //Si l'auteur modifie l'animation alors qu'elle est déjà validée, elle repasse en "non validée"
        if ($author->id == $myUserRequest['id'] && $animationUpdate['validate'] == true)
        {
            $animationUpdate->validate = false;
        }else
        {
            $animationUpdate->validate = $myAnimationRequest['validate'];
        }
        $animationUpdate->time = $myAnimationRequest['time'];
        if($request->adminAnimator){$animationUpdate->user_id = $request->adminAnimator;}else{$animationUpdate->user_id = $myAnimationRequest['user_id'];}
        $animationUpdate->other_time = $myAnimationRequest['other_time'];
        $animationUpdate->registration = $myAnimationRequest['registration'];
        $animationUpdate->registration_date = $myAnimationRequest['registration_date'];
        if($request->hasFile('picture')){$animationUpdate->picture = "images/animations/$filename";}


        $animationUpdate->save();

        //Log::info("---Controller Inscripton : update Animation |  Request 3---");
        //Log::info($animationUpdate);

        

        if ($animationUpdate->validate == true)
        {
            
            //Log::info("---Controller Inscripton : update Animation |  verif author---");
            // On positionne le statut du membre en animateur sauf pour les admins
            if ($author->type != "admin")
            {   
                $author->type="animateur";
                $author->save();
            }
            
            // Ajout du master en 1 dans la table event-user
            $evenementActif = Evenement::where('actif', 1)->first();
            Evenement_user::where('evenement_id', $evenementActif->id)
            ->where('user_id', $author->id)
            ->update(['masters' => true]);


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
        //Log::info("---Function Animation : Destroy---");
        $request->validate([
            "id" => "required|integer",
        ]);

        //Log::info($request);

        $animationDestroy = Animation::findOrFail($request->id);
        $animationDestroy->delete();
        
        Log::info("JOURNAL : ---Controller ANIMATION DESTROY : SUPRESSION de l'animation $request->id ---");

        return response()->json([
            'status' => 'true',
            'message' => 'L\'animation a été supprimé !',
            'Animation supprimée : ' => $request->id
        ]);
    }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationDuplicate ~~~~~~~~~~~~~~~~~~~~~~~~~~
        public function animationDuplicate (Request $request)
        {
            $request->validate([
                "id" => "required|integer",
            ]);
    
    
            $Animation = Animation::findOrFail($request->id);
    
            $newAnimation = $Animation->replicate();

            //Changement de statut pour permettre a l'orga de modifier des éléments.
            $newAnimation->validate = 0;

            $newAnimation->save();
            
            Log::info("JOURNAL : ---Controller ANIMATION DUPLICATE : DUPLICATE de l'animation $request->id -> $newAnimation->id ---");
    
            return response()->json([
                'status' => 'true',
                'message' => 'L\'animation a été dupliquée !',
                'idNewAnimation' => $newAnimation->id
            ]);
        }
}

