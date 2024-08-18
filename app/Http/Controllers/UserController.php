<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Nette\Utils\Random;

class UserController extends Controller
{
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER : Index ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function userlist() {
        // Récupère tous les users enregistrés dans la bdd
        Log::info("---LIST USER---");
        // $users = DB::table('users')->get();
        return User::select('id','lastname','firstname','birthday','phone','email', 'type', 'picture', 'presentation')->get();
        // Log::info($users);
        // Génère pour chaque lieu une url de l'image associée au lieu
        // foreach ($users as $user) {
        //     $user->file = asset('storage/images/' . $user->file);
        // }

        // $token = $request->bearerToken;
        // $token = $user->createToken('remember_token')->plainTextToken;

        return response()->json([
            'status' => 'true',
            // 'token' => $token,
            'message' => 'Voici vos users !',
            // $users
        ]);
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER USER : Create ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function useradd(Request $request)
    {
        Log::info("---CREA USER---");
        $request->validate([
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'birthday' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            /*'password' => [
                'required',
                // 'confirmed',
                'string',
                'min:10',             // doit comporter au moins 10 caractères
                'regex:/[a-z]/',      // doit contenir au moins une lettre minuscule
                'regex:/[A-Z]/',      // doit contenir au moins une lettre majuscule
                'regex:/[0-9]/',      // doit contenir au moins un chiffre
                'regex:/[@$!%*#?&]/', // doit contenir un caractère spécial
            ],*/
        ]);

        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/users/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }else{

            $filename = 'img_default_drake.jpg';
        }

        Log::info($request);

        $user = User::create([
            'lastname' => $request->lastname,
            'firstname' => $request->firstname,
            'email' => $request->email,
            'birthday' => $request->birthday,
            'password' => bcrypt(Str::random(12)),
            'type' => $request->type,
        ]);

        $token = $user->createToken('remember_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Inscription réussie !'
        ], 201);
        Log::info($user);
    }
  
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER : OrganizerIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function organizerlist() {
        // Récupère tous les users enregistrés dans la bdd
        Log::info("---User Controller (OrganizerIndex | Request 1/1) ---");
        // $users = DB::table('users')->get();
        return User::select('id','lastname','firstname','birthday','phone','email', 'type', 'picture', 'presentation')->where('type', '=', "admin")->get();
        // Log::info($users);
        // Génère pour chaque lieu une url de l'image associée au lieu
        // foreach ($users as $user) {
        //     $user->file = asset('storage/images/' . $user->file);
        // }

        // $token = $request->bearerToken;
        // $token = $user->createToken('remember_token')->plainTextToken;

        return response()->json([
            'status' => 'true',
            'message' => 'Voici vos orgas !',
            // $users
        ]);
    }



    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER USER : EDIT ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Show the form for editing the specified resource.
     */
    public function userShow(Request $request, Int $id): JsonResponse 
    {

        // Récupère le user par son ID
        $userShow = User::find($id);

        Log::info("---User Controller (Show | Request 1/1) ---");
        // $userShow = $request->user();

        $userData = [
            'id' => $userShow->id,
            'lastname' => $userShow->lastname,
            'firstname' => $userShow->firstname,
            'picture' => $userShow->picture,
            'birthday' => $userShow->birthday,
            'phone' => $userShow->phone,
            'email' => $userShow->email,
            'password' => $userShow->password,
            'presentation' => $userShow->presentation,
            'type' => $userShow->type,
        ];

        Log::info($userData);

        // return response()->json(['success' => $userData]);
        return response()->json([
            'status' => 'true',
            'message' => 'Voici le profil !',
            // 'User profile : ' => $userData,
            'id' => $userShow->id,
            'lastname' => $userShow->lastname,
            'firstname' => $userShow->firstname,
            'picture' => $userShow->picture,
            'birthday' => $userShow->birthday,
            'phone' => $userShow->phone,
            'email' => $userShow->email,
            'password' => $userShow->password,
            'presentation' => $userShow->presentation,
            'type' => $userShow->type,
        ]);
    }


    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER USER : Update ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Update the specified resource in storage.
     */
    public function userUpdate(Request $request) 
    {
        Log::info("---User Controller (Update | Request 1) ---");

        Log::info($request);

        $request->validate([
            'lastname' => 'required',
            'firstname' => 'required',
            'birthday' => 'required',
            'email' => 'required',
        ]);

        Log::info("---User Controller (Update | Request 2) ---");
        Log::info($request);

        // Récupère l'utilisateur par son ID
        $userUpdate = User::findOrFail($request->id);
        $userUpdate->lastname = $request->lastname;
        $userUpdate->firstname = $request->firstname;
        $userUpdate->picture = $request->picture;
        $userUpdate->birthday = $request->birthday;
        $userUpdate->phone = $request->phone;
        $userUpdate->email = $request->email;
        $userUpdate->password = $request->password;
        $userUpdate->presentation = $request->presentation;

        $userUpdate->save();

        Log::info("---User Controller (Update | Request 3) ---");
        Log::info($userUpdate);

        return response()->json([
            'status' => 'true',
            'message' => 'Profil utilisateur mis à jour avec succès',
            'user' => $userUpdate,
        ]);

    }

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USERS : Destroy ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function userDelete(Request $request)
    {
        Log::info("---User Controller (Destroy | Request 1/1) ---");
        $request->validate([
            "id" => "required|integer",
        ]);

        Log::info($request);

        $userDelete = User::findOrFail($request->id);
        $userDelete->delete();
               
        return response()->json([
            'status' => 'true',
            'message' => 'L\'utilisateur a été supprimé !',
            'User supprimé : ' => $request->id
        ]);
    }

}
