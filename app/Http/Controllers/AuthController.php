<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\info;

class AuthController extends Controller
{
    
    public function login(Request $request)
    {
        //Log::info("---LOGIN---");
        $user = User::where('email', $request->email)->first();


        //Log::info($user);
        if ($user && Hash::check($request->password, $user->password)) {
            // Utilisation de Sanctum pour créer un jeton d'accès
            $token = $user->createToken(time())->plainTextToken;
            Log::info($token);
            return response()->json([
                'token' => $token,
                'user' => $user,
                'user_id' => $user->id,
                'type' => $user->type
            ]);
        } else {
            return response()->json(['error login' => 'Email ou mot de passe incorrect'], 401);
        }
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
        $request->user()->tokens()->delete();
        return response()->json(['Le message de la voix off' => 'Logged out']);
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
