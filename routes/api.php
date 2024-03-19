<?php

use App\Http\Controllers\AnimationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ LOGIN ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::post('/login', [AuthController::class, 'login'])->name('login');

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER ~~~<~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->prefix('user')->group(function () {

    // add a user to the database
    Route::post('/useradd', [UserController::class, 'useradd']);
    // return a page that shows all user
    Route::get('/userlist', [UserController::class, 'userlist']);
    // returns the form for editing a user
    Route::get('/userprofile/{id}', [UserController::class, 'userShow']);
    // updates a user
    Route::put('/userprofile/{id}', [UserController::class, 'userUpdate']);
    // delete a user
    Route::delete('/userlist', [UserController::class, 'userDelete']);
    // Dashboard user
    Route::get('/dashboard', [AuthController::class, 'dashboard']); 
});

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATIONS ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->prefix('animation')->group(function () {
    // return a page that shows all animation
    Route::get('/animationIndex', [AnimationController::class, 'animationIndex']);
    // add an animation to the database
    Route::post('/animationCreate', [AnimationController::class, 'animationCreate']);
    /// return the form for editing an animation
    Route::get('/animationShow/{id}', [AnimationController::class, 'animationShow']);
    // register an user to an animation
    Route::post('/animationShow/{id}', [AnimationController::class, 'animationRegister']);
    // udate an animation
    Route::put('/animationShow/{id}', [AnimationController::class, 'animationUpdate']);
    // delete an animation
    Route::delete('/animationShow/{id}', [AnimationController::class, 'animationDestroy']); 
});

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ LOGOUT ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);