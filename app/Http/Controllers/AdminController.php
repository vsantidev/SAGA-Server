<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // =================================================================================
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~ CONTROLLER ADMIN : Create BUG ~~~~~~~~~~~~~~~~~~~~~~~~~~
    /**
     * Show the form for creating a new resource.
     */
    public function createBug(Request $request)
    {
        Log::info("---BUG CONTROLLER : Function Create ---");
        //Log::info($request);

        $user = User::where('id', $request->userId)->first();
        //Log::info($user);
        //Log::info("---BUG CREATE : BugCreate avant JSON---");

        Mail::send('emails.new-bug', ['senderFirstname' => $user->firstname, 'senderLastname' => $user->lastname ,'description' => $request->description, 'subject' => $request->subject ], function ($message){
            $message->to("jsowyvern@gmail.com")
                    ->subject('formulaire de bug-amelioration');

            // Ajouter des en-têtes personnalisés
            $message->replyTo('contesdautomne@contes-de-provence.com', 'SAGA Contes d automnes');
            $message->getHeaders()->addTextHeader('X-Mailer', 'Laravel Mailer');
            $message->getHeaders()->addTextHeader('X-Priority', '3'); // Priorité normale

            });
        
        Log::info("JOURNAL : ---Controller ADMIN : $user->firstname $user->lastname à soumis une demande de bug/amélio ---");
        
        return response()->json(['message' => 'Le formulaire a bien été envoyé à l\'equipe technique'], 200);
    }
}