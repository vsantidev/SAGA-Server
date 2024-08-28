<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evenement_user;
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

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER IMPORT CSV ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function uploadcsv(Request $request)
    {
        Log::info("---UPLOAD CSV---");
        // Valider que le fichier est bien un CSV
        /*$request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);*/
        //Log::info($request);
        // Récupérer le fichier
        /*$file = $request->file('file');

        // Lire le fichier CSV
        $data = array_map('str_getcsv', file($file));

        // Extraire les en-têtes du CSV (la première ligne)
        $headers = array_shift($data);*/

        // Insérer les données dans la table 'users'

        $usersArray = $request->all();


        foreach ($usersArray as $row) {
            //$userData = array_combine($headers, $row);
            $user = User::create([
                'lastname' => strtoupper($row['lastname']),
                'firstname' => ucfirst(strtolower($row['firstname'])),
                'email' => strtolower($row['email']),
                'password' => bcrypt(Str::random(12)),
            ]);
            //Log::info('USER!');
            //Log::info($user);

            Evenement_user::create([
                'evenement_id' => '1', // A modifier dans le futur pour le prochain evenement.
                'user_id'  => $user->id,
            ]);
        }

        return response()->json([
            'message' => 'Les utilisateurs ont été importés',
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
            'email' => 'required|string|email|max:255|unique:users',
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
            'password' => bcrypt(Str::random(12)),
        ]);
        Log::info('USER!');
        Log::info($user);

        $token = $user->createToken('remember_token')->plainTextToken;

        Evenement_user::create([
            'evenement_id' => '1', // A modifier dans le futur pour le prochain evenement.
            'user_id'  => $user->id,
        ]);


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
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER : AnimatorIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function animatorIndex() {
        
        Log::info("---User Controller (AnimatorIndex | Request 1/1) ---");
        // $users = DB::table('users')->get();
        return User::select('id','lastname','firstname','birthday','phone','email', 'type', 'picture', 'presentation')->where('type', '=', "animateur")->get();
        // Log::info($users);
        // Génère pour chaque lieu une url de l'image associée au lieu
        // foreach ($users as $user) {
        //     $user->file = asset('storage/images/' . $user->file);
        // }

        // $token = $request->bearerToken;
        // $token = $user->createToken('remember_token')->plainTextToken;

        return response()->json([
            'status' => 'true',
            'message' => 'Voici les animateurs !',
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
        //Log::info("---User Controller (Update | Request 1) ---");

        //Log::info($request);
        // Verification de présence d'image et gestion :
        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/users/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }

        //transformation du String en tableau
        $myUserRequest = json_decode($request->user, true); 
    
        // Récupère l'utilisateur par son ID
        $userUpdate = User::findOrFail($myUserRequest['id']);
        $userUpdate->lastname = $myUserRequest['lastname'];
        $userUpdate->firstname = $myUserRequest['firstname'];
        if($request->hasFile('picture')){$userUpdate->picture = "images/users/$filename";}
        $userUpdate->birthday = $myUserRequest['birthday'];
        $userUpdate->phone = $myUserRequest['phone'];
        $userUpdate->email = $myUserRequest['email'];
        $userUpdate->presentation = $myUserRequest['presentation'];

        $userUpdate->save();

        //Log::info("---User Controller (Update | Request 3) ---");
        //Log::info($userUpdate);

        Log::info("JOURNAL : ---Controller USER $userUpdate->firstname $userUpdate->lastname à MAJ son profil: $userUpdate ---");

        return response()->json([
            'status' => 'true',
            'message' => 'Profil utilisateur mis à jour avec succès',
            'user' => $userUpdate,
        ]);

    }

    // =================================================================================

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER USER ADMIN PROFILE: Update ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Update the specified resource in storage.
     */
    public function userAdminUpdate(Request $request)
    {
        Log::info("---User Controller (ADMIN Update | Request 1) ---");

        //Log::info($request);
        // Verification de présence d'image et gestion :
        //transformation du String en tableau
        //$myUserRequest = json_decode($request->user, true); 
    
        // Récupère l'utilisateur par son ID
        $userUpdate = User::findOrFail($request->id);
        $userUpdate->lastname = $request->lastname;
        $userUpdate->firstname = $request->firstname;
        $userUpdate->birthday = $request->birthday;
        $userUpdate->phone = $request->phone;
        $userUpdate->email = $request->email;
        $userUpdate->presentation = $request->presentation;

        $userUpdate->save();

        //Log::info("---User Controller (Update | Request 3) ---");
        //Log::info($userUpdate);

        Log::info("JOURNAL : ---Controller USER $userUpdate->firstname $userUpdate->lastname à MAJ son profil: $userUpdate ---");

        return response()->json([
            'status' => 'true',
            'message' => 'Profil utilisateur mis à jour avec succès',
            'user' => $userUpdate,
        ]);

    }

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER USER : Update MDP ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Update the specified resource in storage.
     */
    public function userUpdateMdp(Request $request) 
    {
        Log::info("---User Controller (UpdateMdp | Request 1) ---");

        $myUserRequest = User::findOrFail($request->user_id);
        Log::info($myUserRequest->password);
        Log::info($myUserRequest->password);
        
        if(password_verify($request->oldPassword, $myUserRequest->password)){
            if($request->newPassword1 == $request->newPassword2){

                $myUserRequest->password = bcrypt($request->newPassword1);
                Log::info("JOURNAL : ---Controller $myUserRequest->firstname $myUserRequest->lastnameuser : $myUserRequest à MAJ son profil ---");
                $myUserRequest->save();
                return response()->json([
                    'status' => 'true',
                    'message' => 'mot de passe utilisateur mis à jour avec succès',
                ]);

            }else{
                return response()->json([
                    'status' => 'false',
                    'message' => 'Confirmation incorrecte : vos mots de passe ne sont pas égaux',
                ]);

            }
        }else{
            return response()->json([
                'status' => 'false',
                'message' => 'erreur sur l\'ancien mot de passe',
            ]);
        }
        //Log::info($request);
        
        //transformation du String en tableau
        //$myUserRequest = json_decode($request->user, true); 

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
