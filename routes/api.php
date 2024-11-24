<?php

use App\Http\Controllers\AnimationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\TypeAnimationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ForgotPasswordController;

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


// ~~~~~~~~~~~~~~~~~~~~~~~~~~ PASSWORD - BUG ~~~~~~~~~~~~~~~~~~~~~~~~~~

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendNewPassword']);



// ~~~~~~~~~~~~~~~~~~~~~~~~~~ USER ~~~<~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    // Verification token on login page
    Route::get('/token', [AuthController::class, 'getUser']);
    // add a user to the database
    Route::post('/useradd', [UserController::class, 'useradd']);
    // return a page that shows all user
    Route::get('/userlist', [UserController::class, 'userlist']);
    // returns the form for editing a user
    Route::get('/userprofile/{id}', [UserController::class, 'userShow']);
    // updates a user
    Route::post('/userprofile/{id}', [UserController::class, 'userUpdate']);
    // update user by admin
    Route::post('/userprofileadmin/{id}', [UserController::class, 'userAdminUpdate']);
    // delete a user
    Route::delete('/userlist', [UserController::class, 'userDelete']);
    // Dashboard user
    Route::get('/dashboard', [AuthController::class, 'dashboard']); 
    // return a page that shows all Orga
    Route::get('/organizerlist', [UserController::class, 'organizerlist']);
    // update mdp
    Route::post('/mdp', [UserController::class, 'userUpdateMdp']); 
    // import users by csv
    Route::post('/userimport', [UserController::class, 'uploadcsv']);
    // import users type animators
    Route::get('/animatorIndex', [UserController::class, 'animatorIndex']); 
    // Reward an animator
    Route::post('/animatorIndex/{id}', [UserController::class, 'animatorReward']);
    // Maj des users / event
    Route::post('/usermaj', [UserController::class, 'usermajevent']);   
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
    // delete an animation to the database
    Route::delete('/animationList', [AnimationController::class, 'animationDestroy']);
    // duplicate an animation to the database
    Route::post('/animationList', [AnimationController::class, 'animationDuplicate']);
    // add an animation to the database
    Route::post('/animationCreate', [AnimationController::class, 'animationCreate']);
    // TODO - importer le type animation pour la creation.
    Route::get('/animationCreate', [TypeAnimationController::class, 'getTypeAnimation']);
    /// return the form for editing an animation
    Route::get('/animationShow/{id}', [AnimationController::class, 'animationShow']);
    // update an animation
    Route::post('/animationShowEdit/{id}', [AnimationController::class, 'animationUpdate']);


    //----ANIMATION -> LIKE----
    // add a like to the database
    Route::post('/animationIndex', [LikeController::class, 'createLike']);
    // delete a like
    Route::delete('/animationIndex', [LikeController::class, 'destroyLike']);

    // register an user to an animation
    Route::post('/animationShow/{id}', [InscriptionController::class, 'createRegister']);
    // unsubcribe a user from an animation
    Route::delete('/animationShow/{id}', [InscriptionController::class, 'destroyRegistration']);

    // ----- Animation -> Type_Animation
    Route::get('/typeAnimation', [TypeAnimationController::class, 'getTypeAnimation']);


});

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ SPONSORS ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->prefix('sponsors')->group(function () {
    Route::get('/index', [SponsorController::class, 'sponsorsIndex']);
    Route::post('/create', [SponsorController::class, 'create']);
    Route::get('/edit/{id}', [SponsorController::class, 'edit']);
    Route::put('/edit/{id}', [SponsorController::class, 'update']);
    Route::delete('/index', [SponsorController::class, 'destroy']);

});

Route::middleware('auth:sanctum')->prefix('animationAdmin')->group(function () {
    Route::post('/animationShow/{id}', [AnimationController::class, 'createValidation']);
    // register an user to an animation (admin)
    Route::post('/animationShow/{id}', [InscriptionController::class, 'createRegisterAdmin']);
    // unsubcribe the user from an animation (Admin)
    Route::delete('/animationShow/{id}', [InscriptionController::class, 'destroyRegistrationAdmin']);
});

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ LOGOUT ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// ~~~~~~~~~~~~~~~~~~~~~~~~~~ ADMIN - BUG ~~~~~~~~~~~~~~~~~~~~~~~~~~
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/bug', [AdminController::class, 'createBug']);
});