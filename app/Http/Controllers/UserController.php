<?php

namespace App\Http\Controllers;

use App\Models\Animation;
use App\Models\Inscription;
use App\Models\User;
use App\Models\Evenement;
use App\Models\Evenement_user;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        ->distinct()
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

        // Convention active vérifiée avant toute écriture
        $evenementActif = Evenement::where('actif', 1)->first();
        if (!$evenementActif) {
            return response()->json([
                'message' => "Aucune convention active : activez-la avant l'import.",
            ], 422);
        }

        // Lignes vides ignorées (saut de ligne en fin de fichier, lignes blanches d'Excel)
        $usersArray = [];
        $erreurs = [];
        foreach ($request->all() as $index => $row) {
            $row = is_array($row) ? array_map(fn($v) => is_string($v) ? trim($v) : $v, $row) : [];
            if (implode('', array_filter($row, 'is_string')) === '') {
                continue;
            }
            $validator = Validator::make($row, [
                'lastname'  => 'required|string|max:255',
                'firstname' => 'required|string|max:255',
                'email'     => 'required|email|max:255',
            ], [
                'lastname.required'  => 'nom manquant',
                'firstname.required' => 'prénom manquant',
                'email.required'     => 'e-mail manquant',
                'email.email'        => 'e-mail invalide',
            ]);
            if ($validator->fails()) {
                // Ligne du fichier : la ligne 1 est l'en-tête
                $erreurs[] = 'ligne ' . ($index + 2) . ' : ' . implode(', ', $validator->errors()->all());
                continue;
            }
            $usersArray[] = $row;
        }

        // Une ligne invalide : rien n'est importé, l'admin corrige et relance
        if ($erreurs) {
            return response()->json([
                'message' => "Fichier refusé, rien n'a été importé.",
                'erreurs' => $erreurs,
            ], 422);
        }
        if (!$usersArray) {
            return response()->json([
                'message' => 'Le fichier ne contient aucun utilisateur.',
            ], 422);
        }

        $nbCrees = 0;

        DB::transaction(function () use ($usersArray, $evenementActif, &$nbCrees) {
        foreach ($usersArray as $row) {
            //test de la présence d'email    
            $user = User::where('email', strtolower($row['email']))->first();

            // Si l'utilisateur n'existe pas, le crée
            if (!$user) {
                $user = User::create([
                    // Fonctions multioctets : « Lefèvre » donne « LEFÈVRE », « JOSÉ » donne « José », « jean-pierre » donne « Jean-Pierre »
                    'lastname' => mb_strtoupper($row['lastname'], 'UTF-8'),
                    'firstname' => mb_convert_case(mb_strtolower($row['firstname'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8'),
                    'email' => strtolower($row['email']),
                    'password' => bcrypt(Str::random(12)),
                    'picture' => 'images/users/img_default_esprit.png',
                ]);
                $nbCrees++;
            }elseif($user->type != "admin") {
                // 'type' n'est pas dans le $fillable : affectation directe
                $user->type = 'membre';
                // Ancien avatar par défaut remplacé par celui de la convention
                if ($user->picture == 'images/users/img_default_viking.png'){
                    $user->picture = 'images/users/img_default_esprit.png';
                }
                $user->save();
            }

            //Log::info($user);
            // Une seule ligne par user/convention : les lots ne servent qu'à la création
            Evenement_user::firstOrCreate([
                'evenement_id' => $evenementActif->id,
                'user_id'  => $user->id,
            ], [
                'winner_lot_pos' => 0,
                'winner_lot'     => false,
            ]);
        }
        });

        $nbExistants = count($usersArray) - $nbCrees;

        return response()->json([
            'message'   => "Import terminé pour la convention {$evenementActif->title} : {$nbCrees} compte(s) créé(s), {$nbExistants} compte(s) déjà connu(s) rattaché(s).",
            'crees'     => $nbCrees,
            'existants' => $nbExistants,
        ]);
    }

    public function usermajevent(Request $request)
    {
        Log::info("JOURNAL : ---Controller USER USERMAJEVENT : MAJ de la table Evenement_user");
        // Définir l'ID de l'événement souhaité
        $evenementActif = Evenement::where('actif', 1)->first();
        if ($evenementActif) {
            $evenementId = $evenementActif->id;
        
            // Utilisateurs non archivés pas encore rattachés à la convention active
            $users = User::where('type', '!=', 'archive')
                ->whereNotIn('id', function($query) use ($evenementId) {
                    $query->select('user_id')
                        ->from('evenement_users')
                        ->where('evenement_id', $evenementId);
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
                    'winner_lot'     => false,
                    'winner_lot_pos' => 0,
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
            'email' => 'required|string|email|max:255',
        ], [
            'lastname.required' => 'Merci de renseigner le nom',
            'firstname.required' => 'Merci de renseigner le prénom',
            'email.required' => 'Merci de renseigner l\'e-mail',
            'email.email' => 'L\'e-mail n\'est pas valide',
        ]);

        // Le compte est rattaché à la convention active : la vérifier avant toute écriture
        $evenementActif = Evenement::where('actif', 1)->first();
        if (!$evenementActif) {
            return response()->json([
                'status'  => 'false',
                'message' => 'Aucune convention active : activez-en une avant de créer un compte.',
            ], 422);
        }

        // Même normalisation que l'import CSV
        $email = mb_strtolower(trim($request->email));

        // Un participant d'une année précédente existe déjà : il faut le désarchiver, pas le recréer
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return response()->json([
                'status'  => 'false',
                'code'    => 'email_pris',
                'user_id' => $existingUser->id,
                'type'    => $existingUser->type,
                'message' => $existingUser->type === 'archive'
                    ? 'Ce compte existe déjà et il est archivé. Ouvrez son profil pour le réactiver.'
                    : 'Un compte existe déjà avec cet e-mail.',
            ], 409);
        }

        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            //$filename = time() .'.'.$extension;
            $filename = uniqid() . "_" . $file->getClientOriginalName();
            $file->move(public_path('images/users/'), $filename);
            //$payload['picture']= 'public/images/'.$filename;
        }else{

            $filename = 'img_default_esprit.png';
        }

        //Log::info($request);

        // Le compte et son rattachement à la convention sont créés ensemble, ou pas du tout
        $user = DB::transaction(function () use ($request, $email, $filename, $evenementActif) {
            $user = User::create([
                'lastname' => $request->lastname,
                'firstname' => $request->firstname,
                'email' => $email,
                'picture' => "images/users/$filename",
                'password' => bcrypt(Str::random(12)),
            ]);

            Evenement_user::create([
                'evenement_id' => $evenementActif->id,
                'user_id'  => $user->id,
                'winner_lot_pos' => 0,
            ]);

            return $user;
        });

        Log::info("JOURNAL : ---Controller USER ADD : Ajout de l'user $request->lastname $request->firstname");

        return response()->json([
            'user' => $user,
            'message' => 'création réussie !'
        ], 201);
    }
  
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER : OrganizerIndex ~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function organizerlist() {
        // Récupère tous les users enregistrés dans la bdd
        //Log::info("---User Controller (OrganizerIndex | Request 1/1) ---");
        // $users = DB::table('users')->get();
        //return User::select('id','lastname','firstname','birthday','phone','email', 'type', 'picture', 'presentation')->where('type', '=', "admin")->get();
        
        return User::select('id', 'lastname', 'firstname', 'birthday', 'phone', 'email', 'type', 'picture', 'presentation')
        ->where('is_orga', 1)
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
            $evenementActif = Evenement::where('actif', 1)->first();
            if ($evenementActif) {
                $evenementId = $evenementActif->id;
            // Récupération des animateurs
            //$userAnimators = User::select('id','lastname','firstname','picture')->where('type', '=', "animateur")->get();

            // Récupération des animations
            $animations=Animation::select('id', 'title', 'type_animation_id', 'user_id')
            ->where('validate', '=','1')
            ->where('evenement_id', $evenementId) // Filtrer par la convention spécifique
            ->get();
            //Log::info("animations --- >");
            //Log::info($animations);

                  $userAnimators = DB::table('evenement_users as eu')
                ->join('users', 'users.id', '=', 'eu.user_id')
                ->select(
                    'users.id',
                    'users.lastname',
                    'users.firstname',
                    'users.picture',
                    'eu.rewards',
                    'eu.reward_prio',
                    DB::raw('(
                        SELECT COUNT(*) 
                        FROM evenement_users 
                        WHERE user_id = users.id 
                        AND masters = true
                    ) as medals')
                )
                ->where('eu.evenement_id', $evenementId) // Filtrer par la convention spécifique
                ->where('eu.masters', true) // Filtrer uniquement les animateurs (si applicable)
                ->groupBy(
                    'users.id',
                    'users.lastname',
                    'users.firstname',
                    'users.picture',
                    'eu.rewards',
                    'eu.reward_prio'
                )  // ← groupBy pour dédoublonner
                ->orderBy('users.lastname')
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

                Log::info("JOURNAL : ---Controller ANIMATOR REWARD : Modification Reward de l'user $id");

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
        // Profil lisible par le compte lui-même ou par un admin
        $authUser = $request->user();
        if ($authUser->id !== $id && $authUser->type !== 'admin') {
            return response()->json([
                'status' => 'false',
                'message' => "Vous ne pouvez consulter que votre propre profil.",
            ], 403);
        }

        // Récupère le user par son ID
        $userShow = User::findOrFail($id);

        return response()->json([
            'status' => 'true',
            'message' => 'Voici le profil !',
            'id' => $userShow->id,
            'lastname' => $userShow->lastname,
            'firstname' => $userShow->firstname,
            'picture' => $userShow->picture,
            'birthday' => $userShow->birthday,
            'phone' => $userShow->phone,
            'email' => $userShow->email,
            'presentation' => $userShow->presentation,
            'type' => $userShow->type,
            'is_orga' => $userShow->is_orga,
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

        // Contrôle avant l'enregistrement : nom et prénom présents, e-mail valide et libre
        // (hors ce compte), date de naissance facultative
        $validator = Validator::make($myUserRequest, [
            'lastname'  => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userUpdate->id)],
            'birthday'  => 'nullable|date',
        ], self::profileMessages());
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'false',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }
        // Le profil part en JSON dans un champ de formulaire : une date vide arrive en chaîne vide
        if ($userUpdate->birthday === '') {
            $userUpdate->birthday = null;
        }
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

        $request->validate([
            'lastname'  => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->id)],
            'type'      => 'required|in:membre,animateur,admin,archive',
            'is_orga'   => 'boolean',
        ], self::profileMessages());

        // Récupère l'utilisateur par son ID
        $userUpdate = User::findOrFail($request->id);
        $userUpdate->lastname = $request->lastname;
        $userUpdate->firstname = $request->firstname;
        $userUpdate->phone = $request->phone;
        $userUpdate->email = $request->email;
        $userUpdate->presentation = $request->presentation;
        $userUpdate->type = $request->type;
        $userUpdate->is_orga = $request->is_orga;
        //Log::info("---User Controller (ADMIN Update | Request avant save) ---");
        $userUpdate->save();

        //Log::info("---User Controller (Update | Request 3) ---");
        //Log::info($userUpdate);

        // Un compte archivé n'est pas rattaché à la convention active (il serait compté dans les stats)
        $evenementActif = Evenement::where('actif', 1)->first();
        if ($evenementActif && $userUpdate->type !== 'archive') {
            $evenementId = $evenementActif->id;

            $exists = Evenement_user::where('evenement_id', $evenementId)
                ->where('user_id', $userUpdate->id)
                ->exists();

            if (!$exists) {
                Evenement_user::create([
                    'evenement_id' => $evenementId,
                    'user_id'      => $userUpdate->id,
                ]);
            }
        }

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
        // Le mot de passe modifié est celui du compte connecté
        $myUserRequest = $request->user();

        $validator = Validator::make($request->all(), [
            'oldPassword'  => 'required|string',
            'newPassword1' => 'required|string|min:6|max:72|same:newPassword2',
        ], [
            'oldPassword.required'  => 'Merci de saisir votre mot de passe actuel.',
            'newPassword1.required' => 'Le mot de passe doit faire au moins 6 caractères.',
            'newPassword1.min'      => 'Le mot de passe doit faire au moins 6 caractères.',
            'newPassword1.max'      => 'Le mot de passe doit faire au plus 72 caractères.',
            'newPassword1.same'     => 'Les deux saisies du nouveau mot de passe ne sont pas identiques.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'false',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->oldPassword, $myUserRequest->password)) {
            return response()->json([
                'status'  => 'false',
                'message' => 'Ancien mot de passe incorrect.',
            ], 422);
        }

        // Le cast 'hashed' du modèle hache le mot de passe à l'enregistrement
        $myUserRequest->password = $request->newPassword1;
        $myUserRequest->save();
        Log::info("JOURNAL : ---Controller $myUserRequest->firstname $myUserRequest->lastname a changé son mot de passe ---");

        return response()->json([
            'status'  => 'true',
            'message' => 'Mot de passe mis à jour.',
        ]);
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

        // Seul un compte sans activité (créé par erreur ou en double) est supprimé : sinon, l'archiver
        $counts = [
            'voeux'      => Inscription::where('user_id', $userDelete->id)->count(),
            'animations' => Animation::where('user_id', $userDelete->id)->count(),
        ];
        if (array_sum($counts) > 0) {
            Log::info("JOURNAL : ---Controller USER DELETE : refus, compte $userDelete->id avec activité ---", $counts);
            return response()->json([
                'status'  => 'false',
                'message' => "Ce compte a {$counts['voeux']} vœu(x) et {$counts['animations']} animation(s) : archivez-le plutôt.",
                'counts'  => $counts,
            ], 409);
        }

        Log::info("JOURNAL : ---Controller USER $userDelete a été supprimé");
        $userDelete->delete();
        
        return response()->json([
            'status' => 'true',
            'message' => 'L\'utilisateur a été supprimé !',
            'User supprimé : ' => $request->id
        ]);
    }

    // Messages des contrôles du profil (joueur et admin)
    private static function profileMessages(): array
    {
        return [
            'lastname.*'  => 'Le nom est obligatoire (255 caractères au plus).',
            'firstname.*' => 'Le prénom est obligatoire (255 caractères au plus).',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'email.*'     => 'L\'adresse e-mail est obligatoire et doit être valide.',
            'birthday.*'  => 'La date de naissance n\'est pas une date valide.',
            'type.*'      => 'Le type doit être membre, animateur, admin ou archive.',
            'is_orga.*'   => 'Organisateur doit valoir oui ou non.',
        ];
    }

}
