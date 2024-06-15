<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Animation;
use App\Models\Inscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;



class LikeController extends Controller
{


    /**
     * Show the form for creating a new resource.
     */
    public function createLike(Request $request): JsonResponse
    {
         // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATION : animationRegister ~~~~~~~~~~~~~~~~~~~~~~~~~~
        Log::info("---Function : Like connected---");
        $request->validate([
            'user_id' => 'required',
            'animation_id' => 'required'
        ]);

        //$userlike = User::find($request->user_id);
        Log::info("---Function : Like Table ---");
        Log::info($request);
        $registerLike = Like::firstOrNew([
            'user_id' => $request->user_id,
            'animation_id' => $request->animation_id
        ]);
        $registerLike->save();
        Log::info($registerLike);


        Log::info("---Function : Like Create---");
        return response()->json([
            'status' => 'true',
            'message' => 'L\'utilisateur a liké l\'animation !',
            // 'User profile : ' => $userData,
            'id' => $request->user_id,
            'animationLike' => $request->animation_id,
        ]);
    }

        /**
     * Remove the specified resource from storage.
     */
    public function destroyLike(Request $request): JsonResponse
    {
        //
        Log::info("---Function Like : Destroy---");
        $request->validate([
            'user_id' => 'required',
            'animation_id' => 'required'
        ]);

        Log::info($request);

        $likeDestroy = Like::where('user_id',$request->user_id)
        ->where('animation_id',$request->animation_id)
        ->delete();
               
        return response()->json([
            'status' => 'true',
            'message' => 'Like a été supprimé !',
            'Like supprimé : ' => $request->animation_id
        ]);
        Log::info("--- FIN Function Like : Destroy---");
    }

    /**
     * Display the specified resource.
     */
    public function showLike(Request $request, Int $id): JsonResponse
    {
        Log::info("---Function Like : Show ---");
        $request->validate([
            "id" => "required|integer",
        ]);

        Log::info($request);

        $likeTable = Like::select('user_id')->where('animation_id', '=', $id)->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Voici le détail de l\'animation !',
            'Like' => $likeTable,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Like $like)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Like $like)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
}
