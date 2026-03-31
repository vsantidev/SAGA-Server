<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Evenement_user;
use App\Models\Animation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class EvenementController extends Controller
{
    //

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

        Log::info("---Evenement CONTROLLER : Function Index ---");

        //Récupération de la liste des sponsors
        $Evenements=Evenement::select('id', 'title', 'date_opening', 'date_ending','actif',)->get();
        Log::info($Evenements);
        return response()->json([
            'status' => 'true',
            'message' => 'Affichage des Evenements',
            'listeEvent' => $Evenements ,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
                //Log::info("---Evenements CREATE : Create---");
                $EvenementCreate = Evenement::create([
                    'actif' => '0',
                    'site_id' => '1',
                    'title' => $request->title,
                    'date_opening' => $request->date_opening,
                    'date_ending' => $request->date_ending,
                ]);

                ///Log::info("---Evenements CREATE : EvenementCreate avant JSON---");
                //Log::info($EvenementCreate);
                Log::info("JOURNAL : ---Controller Evenements : Création de l'evenement : $EvenementCreate");
        
                return response()->json([
                    'event' => $EvenementCreate,
                    'message' => 'Création de l evenement réussi !'
                ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(Evenement $evenement, Int $id)
    {
        // Récupère l evenement par son ID
        $evenementShow = Evenement::find($id);

        //Log::info("---Evenement Controller (Show | Request 1/1) ---");
        // $evenementShow = $request->evenement();

        //Log::info($EvenementData);
        return response()->json([
            'status' => 'true',
            'message' => 'Voici l evenement !',
            'id' => $evenementShow->id,
            'title' => $evenementShow->title,
            'date_opening' => $evenementShow->date_opening,
            'date_ending' => $evenementShow->date_ending,
            'actif' => $evenementShow->actif,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Evenement $evenement): JsonResponse
    {
        //Log::info("---Evenement Controller (Update | Request 1) ---");
        //Log::info($request);

        $request->validate([
            'title' => 'required'
        ]);


        //Log::info("---Evenement Controller (Update | Request 2 Save SQL) ---");
        $evenementUpdate = Evenement::findOrFail($request->id);
        //Log::info("avant update");
        //Log::info($evenementUpdate);
        $evenementUpdate->title = $request->title;
        $evenementUpdate->date_opening = $request->date_opening;
        $evenementUpdate->date_ending = $request->date_ending;
        $evenementUpdate->actif = $request->actif;
        //Si on active un evenement, l'ensemble des autres evenement se desactive.
        if ($evenementUpdate->actif =="1")
        {
            $evenementId=$evenementUpdate->id;
            //les autres conv se desactivent
            Evenement::where('id', '!=', $evenementUpdate->id)->update(['actif' => 0]);
            
            //affectation des admins à la convention (admin appli + orga event non admin + tv)
            $users = User::where(function ($query) {
                $query->where('type', 'admin')
                      ->orWhere('is_orga', true);
            })
            ->whereNotIn('id', function($subquery) use ($evenementId) {
                $subquery->select('user_id')
                    ->from('evenement_users')
                    ->where('evenement_id', $evenementId);
            })
            ->get();
            
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

            if (!empty($data)) {
            Evenement_user::insert($data);
            }


            //Archiver tout les anciens utilisateurs/non orga

            $usersToArchive = DB::table('users')
                ->where('type', '!=', 'admin')
                ->where('is_orga', false)
                ->whereNotIn('id', function ($subquery) use ($evenementId) {
                    $subquery->select('user_id')
                        ->from('evenement_users')
                        ->where('evenement_id', $evenementId);
                })
                ->pluck('id');

            DB::table('users')
                ->whereIn('id', $usersToArchive)
                ->update(['type' => 'archive']);
        }
        
        $evenementUpdate->save();
        //Log::info("aprés update");
        //Log::info($evenementUpdate);

        return response()->json([
            'status' => 'true',
            'message' => 'L evenement a été mise à jour avec succès',
            'event' => $evenementUpdate,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //test si l'evenement recu n'a pas d'animation référencée
        if (!Animation::where('evenement_id', $request->id)->exists()) {
            // Il n'y a aucune animation pour cet événement -> suppr possible

            $evenement = Evenement::find($request->id);
            if (!$evenement) {
                return response()->json([
                    'message' => 'Événement non trouvé.'
                ], 404);
            }
            $evenement->delete();

            
            Log::info("JOURNAL : ---Controller EVENT SUPPR: suppr event, pas d'animation référencée, $request->id ---");

            return response()->json([
                'message' => 'Événement supprimé avec succès.'
            ], 200);

        } else {
            // Il y a déjà des animations liées à cet événement - Suppr impossible
            Log::info("JOURNAL : ---Controller EVENT SUPPR: impossible de suppr event $request->id ---");
            return response()->json([
                'message' => 'Impossible de supprimer des animations existentes!'
            ], 520);
        }
        //
    }

    // GET /eventresults
    public function results(Request $request): JsonResponse
    {
        $request->validate([
            'evenement_id' => 'required|exists:evenements,id',
        ]);

        $winners = Evenement_user::where('evenement_id', $request->evenement_id)
            ->where('masters', true)
            ->whereHas('user', fn($q) => $q->where('is_orga', false))
            ->where('winner_lot', true)
            ->orderBy('winner_lot_pos')
            ->with('user:id,firstname,lastname')
            ->get();

        $total = Evenement_user::where('evenement_id', $request->evenement_id)
            ->where('masters', true)
            ->whereHas('user', fn($q) => $q->where('is_orga', false))
            ->count();

        return response()->json([
            'status'  => 'true',
            'winners' => $winners,
            'total'   => $total,
        ]);
    }

    // POST /eventdraw
    public function draw(Request $request): JsonResponse
    {
        $request->validate([
            'evenement_id' => 'required|exists:evenements,id',
            'nb_lots'      => 'required|integer|min:1',
        ]);

        // Récupérer tous les animateurs de l'événement
        $animateurs = Evenement_user::where('evenement_id', $request->evenement_id)
            ->where('masters', true)
            ->whereHas('user', fn($q) => $q->where('is_orga', false))
            ->get();

        if ($animateurs->count() < $request->nb_lots) {
            return response()->json([
                'status'  => 'false',
                'message' => 'Pas assez d\'animateurs pour ' . $request->nb_lots . ' lots !',
            ], 422);
        }

        // Reset du tirage précédent
        Evenement_user::where('evenement_id', $request->evenement_id)
            ->where('masters', true)
            ->whereHas('user', fn($q) => $q->where('is_orga', false))
            ->update([
                'winner_lot'     => false,
                'winner_lot_pos' => 0,
            ]);

        // Tirage aléatoire
        $shuffled = $animateurs->shuffle()->take($request->nb_lots)->values();

        foreach ($shuffled as $index => $animateur) {
            $animateur->update([
                'winner_lot'     => true,
                'winner_lot_pos' => $index + 1,
            ]);
        }

        // Retourner les gagnants
        $winners = Evenement_user::where('evenement_id', $request->evenement_id)
            ->where('masters', true)
            ->whereHas('user', fn($q) => $q->where('is_orga', false))
            ->where('winner_lot', true)
            ->orderBy('winner_lot_pos')
            ->with('user:id,firstname,lastname')
            ->get();

        return response()->json([
            'status'  => 'true',
            'message' => 'Tirage effectué !',
            'winners' => $winners,
            'total_animateurs' => $animateurs->count(),
        ]);
    }


    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'evenement_id' => 'required|exists:evenements,id',
        ]);

        Evenement_user::where('evenement_id', $request->evenement_id)
            ->where('masters', true)
            ->whereHas('user', fn($q) => $q->where('is_orga', false))
            ->update([
                'winner_lot'     => false,
                'winner_lot_pos' => 0,
            ]);

        return response()->json([
            'status'  => 'true',
            'message' => 'Tirage réinitialisé !',
        ]);
    }
    
}
