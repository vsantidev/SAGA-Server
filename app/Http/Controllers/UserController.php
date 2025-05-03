<?php

namespace App\Http\Controllers;

use App\Models\Animation;
use App\Models\User;
use App\Models\Evenement;
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
        // Récupère tous les users enregistrés dans la bdd et y ajoute un type_code pour gérer le datagrid non payant
        $users = User::selectRaw("
            id,
            lastname,
            firstname,
            phone,
            email,
            type,
            CASE
                WHEN type = 'archive' THEN 0
                WHEN type = 'membre' THEN 1
                WHEN type = 'animateur' THEN 2
                WHEN type = 'admin' THEN 3
                ELSE NULL
            END AS type_code
        ")
        ->orderBy('lastname', 'asc')
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Voici vos users !',
        'data' => $users,
    ]);

    }

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER : LIST OF THE EVENT ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function userlistevent() {
        // Récupère tous les users enregistrés dans la bdd
        Log::info("---LIST USER EVENT---");

        $activeEvent = Evenement::where('actif', 1)->first();
        
        return $Users = User::select('users.id', 'lastname', 'firstname', 'birthday', 'phone', 'email', 'type', 'picture', 'presentation')
        ->join('evenement_users', 'users.id', '=', 'evenement_users.user_id')
        ->where('evenement_users.evenement_id', $activeEvent->id)
        ->orderBy('lastname', 'asc')
        ->get();

        return response()->json([
            'status' => 'true',
            // 'token' => $token,
            'message' => 'Voici vos users de la conv!',
            // $users
        ]);
    }

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER IMPORT CSV ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function uploadcsv(Request $request)
    {
        //Log::info("---UPLOAD CSV---");
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
            //test de la présence d'email    
            $user = User::where('email', strtolower($row['email']))->first();

            // Si l'utilisateur n'existe pas, le crée
            if (!$user) {
                $user = User::create([
                    'lastname' => strtoupper($row['lastname']),
                    'firstname' => ucfirst(strtolower($row['firstname'])),
                    'email' => strtolower($row['email']),
                    'password' => bcrypt(Str::random(12)),
                    'picture' => 'images/users/img_default_viking.png'
                ]);
            }elseif($user->type != "admin") {
                if ($user->picture == '/images/users/img_default_drake.jpg'){
                    $user->update([
                        'type' => 'membre',
                        'picture' => 'images/users/img_default_viking.png'
                    ]);
                }else{
                    $user->update([
                        'type' => 'membre',
                    ]);
                }                
            }

            //Log::info($user);
            $evenementActif = Evenement::where('actif', 1)->first();
            if ($evenementActif) {
                Evenement_user::create([
                    'evenement_id' => $evenementActif->id,
                    'user_id'  => $user->id,
                ]);
            } else {
                // Gère le cas où aucun événement actif n'est trouvé
                // Tu peux logger ou lancer une exception selon le besoin
                Log::warning('Aucun événement actif trouvé lors de la création d\'Evenement_user.');
            }
        }

        return response()->json([
            'message' => "Les utilisateurs ont été importés et associé à l\evenement : $evenementActif->title (si pas de nom de conv....faire maj user-event)",
        ]);
    }

    public function usermajevent(Request $request)
    {
        Log::info("JOURNAL : ---Controller USER USERMAJEVENT : MAJ de la table Evenement_user");
        // Définir l'ID de l'événement souhaité
        $evenementActif = Evenement::where('actif', 1)->first();
        if ($evenementActif) {
            $evenementId = $evenementActif->id;
        
            // Récupérer les ID des utilisateurs non présents dans `evenement_users` pour cet `evenement_id`
            /*$users = User::whereNotIn('id', function($query) use ($evenementId) {
                $query->select('user_id')
                    ->from('evenement_users')
                    ->where('evenement_id', $evenementId);
            })->get();*/

            // Récupérer les ID des utilisateurs non présents dans `evenement_users`
            $users = User::whereNotIn('id', function($query) {
                $query->select('user_id')
                      ->from('evenement_users');
            })->get();
            

    
            // Préparer les données pour insertion
            $data = $users->map(function($user) use ($evenementId) {
                return [
                    'user_id' => $user->id,
                    'evenement_id' => $evenementId,
                    'masters' => 0,
                    'rewards' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
    
            // Insérer les données en une seule requête
            Evenement_user::insert($data);
        
            return response()->json(['message' => 'Table `evenement_users` remplie avec succès pour les utilisateurs non présents.']);
        }else{
            // Gère le cas où aucun événement actif n'est trouvé
                // Tu peux logger ou lancer une exception selon le besoin
                Log::warning('Aucun événement actif trouvé lors de la création d\'Evenement_user.');
        }
           
    }

    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER USER : Create ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function useradd(Request $request)
    {
        //Log::info("---CREA USER---");
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

            $filename = 'img_default_viking.png';
        }

        //Log::info($request);

        $user = User::create([
            'lastname' => $request->lastname,
            'firstname' => $request->firstname,
            'email' => $request->email,
            'picture' => "images/users/$filename",
            'password' => bcrypt(Str::random(12)),
        ]);
        //Log::info('USER!');
        //Log::info($user);
        $evenementActif = Evenement::where('actif', 1)->first();
        if ($evenementActif) {
            $evenementId = $evenementActif->id;

            $token = $user->createToken('remember_token')->plainTextToken;
            Log::info("JOURNAL : ---Controller USER ADD : Ajout de l'user $request->lastname $request->firstname");
            Evenement_user::create([
                'evenement_id' => $evenementId,
                'user_id'  => $user->id,
            ]);


            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Inscription réussie !'
            ], 201);
            Log::info($user);
        
        }else{
                // Gère le cas où aucun événement actif n'est trouvé
                // Tu peux logger ou lancer une exception selon le besoin
                Log::warning('Aucun événement actif trouvé lors de la création d\'user.');
        }

        
    }
  
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER : OrganizerIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function organizerlist() {
        // Récupère tous les users enregistrés dans la bdd
        //Log::info("---User Controller (OrganizerIndex | Request 1/1) ---");
        // $users = DB::table('users')->get();
        //return User::select('id','lastname','firstname','birthday','phone','email', 'type', 'picture', 'presentation')->where('type', '=', "admin")->get();
        
        return User::select('id', 'lastname', 'firstname', 'birthday', 'phone', 'email', 'type', 'picture', 'presentation')
        ->whereIn('id', [2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15, 16])
        ->get();
        
        return response()->json([
            'status' => 'true',
            'message' => 'Voici vos orgas !',
            // $users
        ]);
    }

        // =================================================================================
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER : AnimatorIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
        public function animatorIndex() {
            
            //Log::info("---User Controller (AnimatorIndex | Request 1/1) ---");
            
            // Récupération des animateurs
            //$userAnimators = User::select('id','lastname','firstname','picture')->where('type', '=', "animateur")->get();

            // Récupération des animations
            $animations=Animation::select('id', 'title', 'type_animation_id', 'user_id')->where('validate', '=','1')->get();
            //Log::info("animations --- >");
            //Log::info($animations);

            //$evenement_users=Evenement_user::select('rewards', 'user_id')->where('evenement_id', '=','1')->get();
            $evenementActif = Evenement::where('actif', 1)->first();
            if ($evenementActif) {
                $evenementId = $evenementActif->id;
        
                $userAnimators = User::select('users.id', 'users.lastname', 'users.firstname', 'users.picture', 'evenement_users.rewards')
                ->join('evenement_users', 'users.id', '=', 'evenement_users.user_id')
                ->where('evenement_users.evenement_id', '=', $evenementId)
                ->where('evenement_users.masters', true)
                ->get();

                return response()->json([
                    'status' => 'true',
                    'message' => 'Voici les animateurs !',
                    'listeAnimateurs' => $userAnimators,
                    'listeAnimations'=> $animations,
                ]);
            }else{
                // Gère le cas où aucun événement actif n'est trouvé
                // Tu peux logger ou lancer une exception selon le besoin
                Log::warning('Aucun événement actif trouvé lors de la création d\'user.');
            }
        }

    public function animatorReward(Request $request, int $id): JsonResponse
    {
        // Validation de la requête pour s'assurer que `rewards` est bien un booléen ou entier binaire
        $validatedData = $request->validate([
            'rewards' => 'required|boolean',  // Assurez-vous que la valeur `rewards` est correcte
        ]);

        try {
            $evenementActif = Evenement::where('actif', 1)->first();
            if ($evenementActif) {
                $evenementId = $evenementActif->id;
                // Rechercher l'enregistrement `Evenement_user` avec le `user_id` et `evenement_id` spécifiés
                $evenementUser = Evenement_user::where('user_id', $id)
                                                ->where('evenement_id', '=', $evenementId)
                                                ->firstOrFail();

                // Mise à jour de la valeur `rewards`
                $evenementUser->rewards = $validatedData['rewards'];
                $evenementUser->save();

                Log::info("JOURNAL : ---Controller ANIMATOR REWARD : Modificationn Reward de l'user $id");

                // Réponse JSON de succès
                return response()->json([
                    'success' => true,
                    'message' => 'Rewards updated successfully',
                    'user' => $evenementUser
                ], 200);
            }else{
                // Gère le cas où aucun événement actif n'est trouvé
                // Tu peux logger ou lancer une exception selon le besoin
                Log::warning('Aucun événement actif trouvé lors de la création d\'user.');
            }

        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse JSON avec un message d'erreur
            return response()->json([
                'success' => false,
                'message' => 'Error updating rewards: ' . $e->getMessage()
            ], 500);
        }
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

        //Log::info("---User Controller (Show | Request 1/1) ---");
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

        //Log::info($userData);

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
        //Log::info("---User Controller (ADMIN Update | Request 1) ---");

        //Log::info($request);
        // Verification de présence d'image et gestion :
        //transformation du String en tableau
        //$myUserRequest = json_decode($request->user, true); 
    
        // Récupère l'utilisateur par son ID
        $userUpdate = User::findOrFail($request->id);
        $userUpdate->lastname = $request->lastname;
        $userUpdate->firstname = $request->firstname;
        $userUpdate->phone = $request->phone;
        $userUpdate->email = $request->email;
        $userUpdate->presentation = $request->presentation;
        $userUpdate->type = $request->type;
        //Log::info("---User Controller (ADMIN Update | Request avant save) ---");
        $userUpdate->save();

        //Log::info("---User Controller (Update | Request 3) ---");
        //Log::info($userUpdate);

        Log::info("JOURNAL : ---Controller USER $userUpdate->firstname $userUpdate->lastname à été MAJ par un ADMIN : $userUpdate ---");

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
        //Log::info("---User Controller (UpdateMdp | Request 1) ---");

        $myUserRequest = User::findOrFail($request->user_id);
        //Log::info($myUserRequest->password);
        //Log::info($myUserRequest->password);
        
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
        //Log::info("---User Controller (Destroy | Request 1/1) ---");
        $request->validate([
            "id" => "required|integer",
        ]);

        //Log::info($request);

        $userDelete = User::findOrFail($request->id);
        Log::info("JOURNAL : ---Controller USER $userDelete a été supprimé");
        $userDelete->delete();
        
        return response()->json([
            'status' => 'true',
            'message' => 'L\'utilisateur a été supprimé !',
            'User supprimé : ' => $request->id
        ]);
    }

}
