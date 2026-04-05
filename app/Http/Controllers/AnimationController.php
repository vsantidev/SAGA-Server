<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Evenement_user;
use App\Models\Animation;
use App\Models\Room;
use App\Models\Inscription;
use App\Models\TimeSlot;
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
use \DateTime;
use \DateInterval;

class AnimationController extends Controller
{
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Accueil
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
            'animations.time_slot_id', 
            'type_animations.type as type_animation_name',
            'rooms.name as room',
            'rooms.picture as roomPicture',
            'time_slots.start_time as slot_start_time',
            'time_slots.draw_status as slot_draw_status',
            'time_slots.name as slot_name',
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
        ->leftJoin('rooms', 'animations.room_id', '=', 'rooms.id')
        ->leftJoin('time_slots', 'animations.time_slot_id', '=', 'time_slots.id')
        ->join('evenements', 'animations.evenement_id', '=', 'evenements.id')
        ->where('evenements.actif', 1)
        ->groupBy('animations.id')
        ->orderBy('time_slots.start_time', 'asc')
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
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationListIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Liste pour les admins
    public function animationListIndex()
    {
        $animations = Animation::with(['type_animation'])
            ->select(
                'animations.id', 'animations.title', 'animations.content', 'animations.type_animation_id',
                'animations.registration_date', 'animations.open_time', 'animations.closed_time',
                'animations.other_time', 'animations.multiple', 'animations.roleplay', 'animations.reflection',
                'animations.fight', 'animations.picture', 'animations.room_id', 'animations.user_id',
                'animations.capacity', 'animations.min_capacity', 'animations.validate', 'animations.system'
            )
            ->join('evenements', 'animations.evenement_id', '=', 'evenements.id')
            ->get();

        $userIds = $animations->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->select('id', 'firstname', 'lastname')->get()->keyBy('id');

        $roomIds = $animations->pluck('room_id')->unique();
        $rooms = Room::whereIn('id', $roomIds)->select('id', 'name','picture')->get()->keyBy('id');

        $animations = $animations->map(function($anim) use ($users, $rooms) {
            $user = $users->get($anim->user_id);
            $room = $rooms->get($anim->room_id);
            return [
                ...$anim->toArray(),
                'room_name'           => $room?->name ?? '',
                'room_picture'           => $room?->picture ?? '',
                'type_animation_name' => $anim->type_animation?->type ?? '',
                'author_name'         => $user
                                            ? ucfirst(strtolower($user->firstname)).' '.strtoupper($user->lastname)
                                            : '',
                'evenement_year'      => \Carbon\Carbon::parse($anim->open_time)->year,
            ];
        });

        return response()->json([
            'status'         => true,
            'message'        => 'Affichage des animations !',
            'listeAnimation' => $animations,
        ]);
    }

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationListIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Calendrier
    public function animationListValidate(Request $request)
    {
        $IdUser = $request->query('param1');

        $animations = Animation::with(['type_animation'])
            ->select(
                'animations.id', 'animations.title', 'animations.content', 'animations.type_animation_id',
                'animations.registration_date', 'animations.open_time', 'animations.closed_time',
                'animations.other_time', 'animations.multiple', 'animations.roleplay', 'animations.reflection',
                'animations.fight', 'animations.picture', 'animations.room_id', 'animations.user_id', 'animations.time_slot_id', 
                'animations.capacity', 'animations.min_capacity', 'animations.validate', 'animations.system','time_slots.start_time as slot_start_time',
            'time_slots.draw_status as slot_draw_status','time_slots.name as slot_name'
            )
            ->where('animations.validate', 1)
            ->where('evenements.actif', 1)
            ->leftJoin('time_slots', 'animations.time_slot_id', '=', 'time_slots.id')
            ->join('evenements', 'animations.evenement_id', '=', 'evenements.id')
            ->join('rooms', 'animations.room_id', '=', 'rooms.id')
            ->where('rooms.active', 1) 
            ->get();

        // Likes de l'utilisateur connecté
        $likedAnimationIds = Like::where('user_id', $IdUser)->pluck('animation_id')->toArray();
        $userIds = $animations->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->select('id', 'firstname', 'lastname')->get()->keyBy('id');

        $roomIds = $animations->pluck('room_id')->unique();
        $rooms = Room::whereIn('id', $roomIds)->select('id', 'name','picture')->get()->keyBy('id');

        $animations = $animations->map(function($anim) use ($users, $rooms, $likedAnimationIds) {
            $user = $users->get($anim->user_id);
            $room = $rooms->get($anim->room_id);
            return [
                ...$anim->toArray(),
                'room_name'           => $room?->name ?? '',
                'room_picture'           => $room?->picture ?? '',
                'type_animation_name' => $anim->type_animation?->type ?? '',
                'author_name'         => $user
                                            ? ucfirst(strtolower($user->firstname)).' '.strtoupper($user->lastname)
                                            : '',
                'evenement_year'      => \Carbon\Carbon::parse($anim->open_time)->year,
                'is_liked'            => in_array($anim->id, $likedAnimationIds),
            ];
        });

        return response()->json([
            'status'         => true,
            'message'        => 'Affichage des animations !',
            'listeAnimation' => $animations,
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
            'fight' => 'required|integer|min:1',
            'reflection' => 'required|integer|min:1',
            'roleplay' => 'required|integer|min:1',
            'open_time' => 'required|date',
            'closed_time' => 'required|date',
            'time' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'type_animation_id' => 'required',
            'time_slot_id'     => 'required|exists:time_slots,id',
        ], [
            'title.required' => 'Merci de renseigner un titre',
            'content.required' => 'Merci de renseigner une description',
            'fight.min' => 'Merci de renseigner une valeur d\'affrontement',
            'reflection.min' => 'Merci de renseigner de stratégie',
            'roleplay.min' => 'Merci de renseigner d\'ambiance',
            'open_time.required' => 'Merci de selectionner une tranche horaire',
            'closed_time.required' => 'Merci de renseigner une tranche horaire',
            'time.required' => 'Merci de renseigner la durée de l\'animation',
            'capacity.integer' => 'Merci de renseigner le nombre de joueurs max',
            'type_animation_id.required' => 'Merci de selectionner un type d\'animation',
            'time_slot_id.required'     => 'Merci de selectionner un créneau'
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

        // Convertir les ids en noms de créneaux
        $otherTimeNames = null;
        if ($request->other_time) {
            $ids = explode(' - ', $request->other_time);
            $otherTimeNames = TimeSlot::whereIn('id', $ids)
                ->pluck('name')
                ->join(' - ');
        } else {
            $otherTimeNames = null;
        }

        //Log::info("---ANIMATION CREATE : fin picture---");

        $DatesEvent = Evenement::select('id','date_opening','date_ending')->where('actif', '=', '1')->first();
        //comparaison dates events
        //Log::info("---ANIMATION CREATE : verif date---");
        
        /*if($request->open_time >= $DatesEvent->date_opening && $request->closed_time <= $DatesEvent->date_ending)
        { */       
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
                'other_time' => $otherTimeNames,
                'capacity' => $request->capacity,
                'min_capacity' => $request->min_capacity,
                'evenement_id' => $DatesEvent->id,
                'type_animation_id' => $request->type_animation_id,
                'user_id' => $request->user_id,
                'registration_date' => $request->registrationDate,
                'picture'=> "images/animations/$filename",
                'system' => $request->filled('system') ? $request->system : null,
                'time_slot_id' => $request->time_slot_id,         
            ]);

            //Log::info("---ANIMATION CREATE : AnimationCreate avant json---");
            //Log::info($animationCreate);

            return response()->json([
                'animation' => $animationCreate,
                'message' => 'Création du formulaire d\'animation réussi !'
            ], 201);

            //Log::info("---ANIMATION CREATE : AnimationCreate après json---");
            Log::info("JOURNAL : ---Controller ANIMATION CREATE: $request->user_id à crée l'animation $request->title ---");
        /*}else{
            return response()->json([
                'message' => 'Erreur dans l\'insertion!'
            ], 520);
        }*/
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
    $animationShow = Animation::find($id);
    $type_animation = Type_animation::find($animationShow->type_animation_id);

    // ~~~~~ INSCRITS avec statut et priorité ~~~~~
    $listInscrits = Inscription::where('animation_id', $id)
        ->join('users', 'inscriptions.user_id', '=', 'users.id')
        ->select(
            'users.id',
            'users.firstname',
            'users.lastname',
            'inscriptions.weight',
            'inscriptions.status',
            'inscriptions.registered_at'
        )
        ->orderBy('inscriptions.weight')
        ->orderBy('inscriptions.registered_at')
        ->get();

    // ~~~~~ SALLE ~~~~~
    $RoomAnim = null;
    if ($animationShow->room_id) {
        $RoomAnim = Room::select('name', 'capacity')
            ->where('id', $animationShow->room_id)
            ->first();
        if ($animationShow->capacity == null && $RoomAnim) {
            $animationShow->capacity = $RoomAnim->capacity;
        }
    }

    // ~~~~~ NB LIKES ~~~~~
    $animationShow->nb_likes = DB::table('likes')
        ->where('animation_id', $animationShow->id)
        ->count();

    // ~~~~~ Status de slot ~~~~~
        $timeSlot = null;
        if ($animationShow->time_slot_id) {
            $timeSlot = \App\Models\TimeSlot::find($animationShow->time_slot_id);
        }

    // ~~~~~ ANIMATIONDATA ~~~~~
    $animationData = [
        'id'                  => $animationShow->id,
        'title'               => $animationShow->title,
        'content'             => $animationShow->content,
        'remark'              => $animationShow->remark,
        'url'                 => $animationShow->url,
        'multiple'            => $animationShow->multiple,
        'picture'             => $animationShow->picture,
        'capacity'            => $animationShow->capacity,
        'min_capacity'        => $animationShow->min_capacity,
        'fight'               => $animationShow->fight,
        'reflection'          => $animationShow->reflection,
        'roleplay'            => $animationShow->roleplay,
        'type_animation_id'   => $type_animation->id,
        'type_animation_name' => $type_animation->type,
        'user_id'             => $animationShow->user_id,
        'validate'            => $animationShow->validate,
        'open_time'           => $animationShow->open_time,
        'closed_time'         => $animationShow->closed_time,
        'time'                => $animationShow->time,
        'other_time'          => $animationShow->other_time,
        'registration'        => $animationShow->registration,
        'registration_date'   => $animationShow->registration_date,
        'nb_likes'            => $animationShow->nb_likes,
        'system'              => $animationShow->system,
        'time_slot_id'        => $animationShow->time_slot_id,
        'slot_draw_status'    => $timeSlot?->draw_status ?? null,
        'room'                => $RoomAnim?->name,
        'room_id'             => $animationShow->room_id,
        'evenement_id'        => $animationShow->evenement_id,
    ];

    // ~~~~~ AUTEUR ~~~~~
    $author = User::findOrFail($animationShow->user_id);
    $author->medals = DB::table('evenement_users')
        ->where('user_id', $author->id)
        ->where('masters', true)
        ->count();

    // ~~~~~ LIKES ~~~~~
    $userLikes = Like::where('animation_id', $animationShow->id)
        ->pluck('user_id')
        ->toArray();

    return response()->json([
        'status'          => 'true',
        'message'         => 'Voici le détail de l\'animation !',
        'listInscrits'    => $listInscrits,
        'animationData'   => $animationData,
        'authorLastname'  => $author->lastname,
        'authorFirstname' => $author->firstname,
        'authorMedals'    => $author->medals,
        'allTypeAnim'     => Type_animation::select('id', 'type')->get(),
        'userLikes'       => $userLikes,
    ]);
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
        if(isset($myAnimationRequest['remark'])){$animationUpdate->remark = $myAnimationRequest['remark'];}
        $animationUpdate->multiple = $myAnimationRequest['multiple'];
        $animationUpdate->picture = $myAnimationRequest['picture'];
        if(isset($myAnimationRequest['capacity'])){$animationUpdate->capacity = $myAnimationRequest['capacity'];}
        if(isset($myAnimationRequest['min_capacity'])){$animationUpdate->min_capacity = $myAnimationRequest['min_capacity'];}
        if(isset($myAnimationRequest['room_id'])){$animationUpdate->room_id = $myAnimationRequest['room_id'];}
        $animationUpdate->fight = $myAnimationRequest['fight'];
        $animationUpdate->reflection = $myAnimationRequest['reflection'];
        $animationUpdate->roleplay = $myAnimationRequest['roleplay'];
        $animationUpdate->type_animation_id = $myAnimationRequest['type_animation_id'];
        if(isset($myAnimationRequest['time_slot_id'])){$animationUpdate->time_slot_id = $myAnimationRequest['time_slot_id'];}
        $animationUpdate->open_time = $myAnimationRequest['open_time'];
        $animationUpdate->closed_time = $myAnimationRequest['closed_time'];
        if(isset($myAnimationRequest['system'])){$animationUpdate->system = $myAnimationRequest['system'];}
        
        if(isset($myAnimationRequest['validate'])){$animationUpdate->validate = $myAnimationRequest['validate'];}
        //Si l'auteur modifie l'animation alors qu'elle est déjà validée, elle repasse en "non validée"
        /*if ($author->id == $myUserRequest['id'] && $animationUpdate['validate'] == true && $author->type != "admin")
        {
            $animationUpdate->validate = false;
        }else
        {
            $animationUpdate->validate = $myAnimationRequest['validate'];
        }*/
        $animationUpdate->time = $myAnimationRequest['time'];
        if($request->adminAnimator){$animationUpdate->user_id = $request->adminAnimator;}else{$animationUpdate->user_id = $myAnimationRequest['user_id'];}
        $animationUpdate->other_time = $myAnimationRequest['other_time'];
        $animationUpdate->registration = $myAnimationRequest['registration'];
        if($request->hasFile('picture')){$animationUpdate->picture = "images/animations/$filename";}

        // Si le champ "time" a changé, on recalcule "closed_time"
        if ($animationUpdate->isDirty('time')) {
        $openTime = new DateTime($myAnimationRequest['open_time']);
            $hoursToAdd = intval($myAnimationRequest['time']); // ex: 4
            $interval = new DateInterval('PT' . $hoursToAdd . 'H');
            $closedTime = clone $openTime;
            $closedTime->add($interval);
            $animationUpdate->closed_time = $closedTime->format('Y-m-d H:i:s');
        }

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
        // ── supprime toutes les inscriptions liées ──
        Inscription::where('animation_id', $request->id)->delete();
        $animationDestroy->delete();
        
        Log::info("JOURNAL : ---Controller ANIMATION DESTROY : SUPRESSION de l'animation $request->id et des inscriptions liées---");

        return response()->json([
            'status' => 'true',
            'message' => 'L\'animation a été supprimé !',
            'Animation supprimée : ' => $request->id
        ]);
    }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationDuplicate ~~~~~~~~~~~~~~~~~~~~~~~~~~
        public function animationDuplicate(Request $request)
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

