<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminaite\Support\Facade\Log;
use Illuminate\Support\Facades\Log as FacadesLog;

use function Laravel\Prompts\info;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'lastname'=> 'required|string|max:255',
            'firstname'=> 'required|string|max:255',
            'birthday'=> 'required',
            'email'=> 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'confirmed',
                'string',
                'min:10',             // doit comporter au moins 10 caractères
                'regex:/[a-z]/',      // doit contenir au moins une lettre minuscule
                'regex:/[A-Z]/',      // doit contenir au moins une lettre majuscule
                'regex:/[0-9]/',      // doit contenir au moins un chiffre
                'regex:/[@$!%*#?&]/', // doit contenir un caractère spécial
            ],
        ]);

        $user = User::create([
            'lastname' => $request->lastname,
            'firstname' => $request->firstname,
            'email' => $request->email,
            'birthday' => $request->birthday,
            'password' => bcrypt($request->password),
            'type' => $request->type,
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function login(Request $request)
    {

        $user = User::where('email', $request->email)->first();

        echo($user);
        if ($user && Hash::check($request->password, $user->password)) {
            // Utilisation de Sanctum pour créer un jeton d'accès
            $token = $user->createToken(time())->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user, 
            ]);
        } else {
            return response()->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }


        // $request->validate([
        //     'email'=> 'required|string|email|',
        //     'password' => 'required|string',
        // ]);

        // if(!Auth::attempt($request->only('email','password'))) {
        //     return response()->json(['message' => 'Invalid detail login'], 401);
        // }

        // $user = $request->user();
        // $token = $user->createToken('authToken')->plainTextToken;
        // return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

}
