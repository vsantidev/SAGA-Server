<?php

namespace App\Http\Controllers;

use App\Models\Animation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnimationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function animationIndex()
    {
        Log::info("--- ANIMATION INDEX ---");
        return Animation::select('id', 'title', 'content', 'created_at')->get();

        // Récupère les type d'animations associés à l'ID de la table animations
        // $type_animations = DB::table('Type_animations')->where('type_animations.animations_id', $id)->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Affichage des animations !'
            // 'type_animations' => $type_animations,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function animationCreate(Request $request)
    {
        Log::info("---ANIMATION CONTROLLER : Function AnimationCreate ---");

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required'
        ]);

        // -> Récupère le user par son ID
        // $user_id = User::findOrFail($id);
        // $user_id = Auth::id();

        Log::info("---ANIMATION CREATE : Request---");
        Log::info($request);

        Log::info("---ANIMATION CREATE : User_id---");
        // Log::info($user_id);

        $animationCreate = Animation::create([
            'title' => $request->title,
            'content' => $request->content,
            'validate' => $request->validate,
            'user_id' => $request->user_id
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

    /**
     * Store a newly created resource in storage.
     */
    public function animationStore(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function animationShow(Request $request, Int $id): JsonResponse
    {   
        // Récupère les type d'animations associés à l'ID de la table animations
        // $type_animations = DB::table('Type_animations')->where('type_animations.animations_id', $id)->get();

        // return response()->json([
        //     'type_animations' => $type_animations
        // ]);

        // Récupère le user par son ID
        $animationShow = Animation::find($id);

        Log::info("---Function : AnimationShow connected---");
        // $userShow = $request->user();

        $animationData = [
            'id' => $animationShow->id,
            'title' => $animationShow->title,
            'content' => $animationShow->content,
            'picture' => $animationShow->picture,
            'capacity' => $animationShow->capacity,
            'fight' => $animationShow->fight,
            'reflection' => $animationShow->reflection,
            'roleplay' => $animationShow->roleplay,
            'type_animation' => $animationShow->type_animation,
            'open_time' => $animationShow->open_time,
            'closed_time' => $animationShow->closed_time
        ];

        Log::info("---Function : AnimationShow Data => ---");
        Log::info($animationData);

        // return response()->json(['success' => $userData]);
        return response()->json([
            'status' => 'true',
            'message' => 'Voici le détail de l\'animation !',
            // 'User profile : ' => $userData,
            'id' => $animationShow->id,
            'title' => $animationShow->title,
            'content' => $animationShow->content,
            'picture' => $animationShow->picture,
            'capacity' => $animationShow->capacity,
            'fight' => $animationShow->fight,
            'reflection' => $animationShow->reflection,
            'roleplay' => $animationShow->roleplay,
            'type_animation' => $animationShow->type_animation,
            'open_time' => $animationShow->open_time,
            'closed_time' => $animationShow->closed_time

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function animationEdit(Animation $animation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function animationUpdate(Request $request)
    {
        Log::info("---Function animationUpdate 1---");

        // $userUpdate = $request->user();

        Log::info($request);

        $request->validate([
            'lastname' => 'required',
            'firstname' => 'required',
            'birthday' => 'required',
            'email' => 'required',
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

    /**
     * Remove the specified resource from storage.
     */
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
