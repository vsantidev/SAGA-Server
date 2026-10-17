<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
        // Changement de mot de passe (UserController::userUpdateMdp) : rien n'est retiré, comme au login
        'oldPassword',
        'newPassword1',
        'newPassword2',
    ];
}
