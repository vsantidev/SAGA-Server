<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    // Mot de passe oublié : envoie un lien pour choisir un nouveau mot de passe (valable 60 minutes).
    // Le mot de passe actuel n'est pas touché : une demande faite par quelqu'un d'autre ne bloque plus le joueur.
    public function sendNewPassword(Request $request)
    {
        // Validation de l'email
        $request->validate(['email' => 'required|email']);
        //Log::info($request);
        // Recherche de l'utilisateur par email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email non trouvé, Veuillez contacter les organisateurs par message privé sur facebook.'], 404);
        }elseif($user->type == 'archive'){
            return response()->json(['message' => 'Cet utilisateur n’est pas présent sur la convention active., Veuillez contacter les organisateurs par message privé sur facebook.'], 404);
        }

        // Jeton à usage unique (table password_reset_tokens) ; un seul envoi par minute et par compte
        $status = Password::sendResetLink(['email' => $user->email], function ($user, $token) {
            $link = rtrim(config('app.front_url'), '/') . '/reset-password?token=' . $token
                . '&email=' . urlencode($user->email);

            Mail::send('emails.reset-link', ['link' => $link], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Choisir un nouveau mot de passe');

                // Ajouter des en-têtes personnalisés
                $message->replyTo('contesdautomne@contes-de-provence.com', 'SAGA Contes d automnes');
                $message->getHeaders()->addTextHeader('X-Mailer', 'Laravel Mailer');
                $message->getHeaders()->addTextHeader('X-Priority', '3'); // Priorité normale
            });
        });

        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['message' => 'Un lien vient déjà d’être envoyé : patientez une minute avant d’en demander un autre.'], 429);
        }

        Log::info("JOURNAL : ---Controller PASSWORDFORGOT : lien de réinitialisation envoyé à $request->email ---");
        return response()->json(['message' => 'Un lien pour choisir un nouveau mot de passe a été envoyé à votre adresse email (valable 60 minutes). Ce message étant automatique, pensez à vérifier dans les courriers indésirables.'], 200);
    }

    // Nouveau mot de passe choisi depuis le lien reçu par e-mail
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|max:72|confirmed',
        ], [
            'password.required'  => 'Le mot de passe doit faire au moins 6 caractères.',
            'password.min'       => 'Le mot de passe doit faire au moins 6 caractères.',
            'password.max'       => 'Le mot de passe doit faire au plus 72 caractères.',
            'password.confirmed' => 'Les deux saisies du nouveau mot de passe ne sont pas identiques.',
        ]);

        $status = Password::reset(
            $request->only('email', 'token', 'password', 'password_confirmation'),
            function (User $user, string $password) {
                // Le cast 'hashed' du modèle hache le mot de passe à l'enregistrement
                $user->password = $password;
                $user->save();
                // Les sessions ouvertes avec l'ancien mot de passe sont fermées
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Ce lien n’est plus valable (déjà utilisé ou plus de 60 minutes). Faites une nouvelle demande depuis « Mot de passe oublié ».'], 422);
        }

        Log::info("JOURNAL : ---Controller PASSWORDFORGOT : $request->email a choisi un nouveau mot de passe ---");
        return response()->json(['message' => 'Mot de passe mis à jour : vous pouvez vous connecter.'], 200);
    }
}
