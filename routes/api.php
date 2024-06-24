<?php

use App\Http\Controllers\AnimationController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\TypeAnimationController;
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

Route::middleware('auth:sanctum')->prefix('like')->group(function () {

    // return a page that shows all user
    Route::get('/animationIndex', [LikeController::class, 'likeShow']);
    // updates a user

});


// ~~~~~~~~~~~~~~~~~~~~~~~~~~ ANIMATIONS ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->prefix('animation')->group(function () {
    // return a page that shows all animation
    Route::get('/animationIndex', [AnimationController::class, 'animationIndex']);
    Route::get('/animationList', [AnimationController::class, 'animationListIndex']);
    // add an animation to the database
    Route::post('/animationCreate', [AnimationController::class, 'animationCreate']);


    Route::get('/animationCreate', [TypeAnimationController::class, 'indexTypeAnimation']);
    /// return the form for editing an animation
    Route::get('/animationShow/{id}', [AnimationController::class, 'animationShow']);


    // update an animation
    Route::put('/animationShow/{id}', [AnimationController::class, 'animationUpdate']);
    // delete an animation

    Route::delete('/animationShow/{id}', [AnimationController::class, 'animationDestroy']); 

    //----ANIMATION -> LIKE----
    // add a like to the database
    Route::post('/animationIndex', [LikeController::class, 'createLike']);
    // delete a like
    Route::delete('/animationIndex', [LikeController::class, 'destroyLike']);

    // Route::delete('/animationShow/{id}', [AnimationController::class, 'animationDestroy']);


    // delete an animation of the list
    // Route::delete('/animationIndex', [AnimationController::class, 'animationDestroy']);


    // register an user to an animation
    Route::post('/animationShow/{id}', [InscriptionController::class, 'createRegister']);
    // unsubcribe a user from an animation
    Route::delete('/animationShow/{id}', [InscriptionController::class, 'destroyRegistration']);


});

Route::middleware('auth:sanctum')->prefix('animationAdmin')->group(function () {
    Route::post('/animationShowAdmin/{id}', [AnimationController::class, 'createValidation']);
    // unsubcribe the user from an animation (Admin)
    Route::delete('/animationShowAdmin/{id}', [InscriptionController::class, 'destroyRegistrationAdmin']);
});

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ LOGOUT ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);