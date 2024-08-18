<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    public function sendNewPassword(Request $request)
    {
        // Validation de l'email
        $request->validate(['email' => 'required|email']);
        Log::info($request);
        // Recherche de l'utilisateur par email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email non trouvé, Veuillez contacter les organisateurs par message privé sur facebook.'], 404);
        }

        // Générer un mot de passe aléatoire
        $newPassword = Str::random(8);
        // Mettre à jour le mot de passe de l'utilisateur
        $user->password = Hash::make($newPassword);
        $user->save();
        Log::info("JOURNAL : ---Controller PASSWORDFORGOT : $request->email à réinitialisé son password ---");
        // Envoyer un email avec le nouveau mot de passe
        Mail::send('emails.new-password', ['newPassword' => $newPassword], function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Votre nouveau mot de passe');

            // Ajouter des en-têtes personnalisés
            $message->replyTo('contesdautomne@contes-de-provence.com', 'SAGA Contes d automnes');
            $message->getHeaders()->addTextHeader('X-Mailer', 'Laravel Mailer');
            $message->getHeaders()->addTextHeader('X-Priority', '3'); // Priorité normale


            });
        return response()->json(['message' => 'Un nouveau mot de passe a été envoyé à votre adresse email, ce message étant automatique, pensez à verifier dans les courriers indésirables'], 200);

    }
}
