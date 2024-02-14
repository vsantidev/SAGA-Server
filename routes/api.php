<?php

use App\Http\Controllers\AnimationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

use Illuminate\Http\Request;
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

// returns the home page with all posts
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    // --------------- USER ---------------
    // adds a user to the database
    Route::post('/useradd', [UserController::class, 'useradd']);
    // returns the form for adding a user
    Route::get('/userlist', [UserController::class, 'userlist']);
    // returns a page that shows a full user
    Route::get('/userprofile/{id}', [UserController::class, 'userShow']);
    // returns the form for editing a user

    // updates a user
    Route::put('/userprofile/{id}', [UserController::class, 'userUpdate']);
    // deletes a user
    Route::delete('/userlist', [UserController::class, 'userDelete']);
    Route::get('/dashboard', [AuthController::class, 'dashboard']); 
});

// --------------- ANIMATIONS ---------------
Route::middleware('auth:sanctum')->prefix('animation')->group(function () {

    // returns the form for adding an animation
    Route::get('/animationIndex', [AnimationController::class, 'animationIndex']);
    // adds an animation to the database
    Route::post('/animationCreate', [AnimationController::class, 'animationCreate']);
    // returns a page that shows a full animation
    Route::get('/animationShow/{id}', [AnimationController::class, 'animationShow']);

});


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });





Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);