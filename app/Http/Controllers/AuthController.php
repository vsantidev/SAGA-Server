<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;

class AuthController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $decaySeconds = 300; // 15 minutes
    
    public function login(Request $request): JsonResponse
    {
        // 1. Vérifie si bloqué
        if ($this->isLocked($request)) {
            return $this->lockoutResponse($request);
        }

        // Un mot de passe vide est refusé, même si un ancien compte en a un
        if (!is_string($request->email) || !is_string($request->password) || $request->password === '') {
            return response()->json([
                'message' => 'Merci de saisir votre e-mail et votre mot de passe.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {

            if ($user->type === 'archive') {
                // On ne réinitialise PAS le compteur — ce n'est pas un succès légitime
                return response()->json([
                    'message' => 'L\'utilisateur a été archivé, contactez un administrateur'
                ], 401);
            }

            // 2. Succès → on efface le compteur
            $this->clearAttempts($request);

            $token = $user->createToken(time())->plainTextToken;

            return response()->json([
                'token'   => $token,
                'user'    => $user,
                'user_id' => $user->id,
                'type'    => $user->type,
            ]);

        } else {
            // 3. Échec → on incrémente
            $this->incrementAttempts($request);

            $remaining = $this->attemptsLeft($request);

            // Si c'était la dernière tentative, on renvoie directement le lockout
            if ($remaining === 0) {
                return $this->lockoutResponse($request);
            }

            return response()->json([
                'message'       => 'Email ou mot de passe incorrect',
                'attempts_left' => $remaining,
            ], 401);
        }
    }

    // -------------------------------------------------------
    // Helpers RateLimiter (privés)
    // -------------------------------------------------------

    private function throttleKey(Request $request): string
    {
        // Clé unique : email (normalisé) + IP
        return Str::lower(trim($request->email)) . '|' . $request->ip();
    }

    private function isLocked(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->throttleKey($request),
            $this->maxAttempts
        );
    }

    private function incrementAttempts(Request $request): void
    {
        RateLimiter::hit($this->throttleKey($request), $this->decaySeconds);
    }

    private function clearAttempts(Request $request): void
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    private function attemptsLeft(Request $request): int
    {
        return RateLimiter::remaining(
            $this->throttleKey($request),
            $this->maxAttempts
        );
    }

    private function lockoutResponse(Request $request): JsonResponse
    {
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        Log::warning('Blocage login - trop de tentatives', [
            'email' => $request->email,
            'ip'    => $request->ip(),
            'retry_after_seconds' => $seconds,
        ]);

        return response()->json([
            'message'     => 'Trop de tentatives. Compte temporairement bloqué.',
            'retry_after' => $seconds,
            'retry_in'    => ceil($seconds / 60) . ' minute(s)',
        ], 429);
    }


    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $userData = [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            // 'pseudo' => $user->username,
            'email' => $user->email,
            // 'birthday' => $user->birthday,
            // 'password' => $user->password,
            'type' => $user->role

        ];
        return response()->json(['success' => $userData]);

    }

    public function logout(Request $request)
    {
        // Ne ferme que la session de cet appareil (tokens()->delete() déconnectait tous les appareils)
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté.']);
    }

    public function getUser(Request $request)
    {
        //Log::info("---GET USER LOGIN---");
        return response()->json([
            'user' => $request->user(),
            'message' => 'Token is valid',
        ]);
    }

}
