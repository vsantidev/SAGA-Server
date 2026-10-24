<?php

namespace App\Exceptions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;


class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Toujours du JSON 401 pour l'API : la redirection vers « login » (une route POST)
        // donnait une 405 en HTML, et l'écran ne savait pas que la session était finie
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Session expirée ou invalide : reconnectez-vous.'], 401);
        }

        return redirect()->guest(route('login'));
    }

    public function render($request, Throwable $exception)
{
    // Trop de requêtes : message en français, avec le délai d'attente renvoyé par la limite
    if ($exception instanceof ThrottleRequestsException) {
        $headers = $exception->getHeaders();
        $wait = (int) ($headers['Retry-After'] ?? 60);
        return response()->json([
            'message' => "Trop de requêtes : patientez {$wait} secondes avant de réessayer.",
            'retry_after' => $wait,
        ], 429, $headers);
    }

    if ($exception instanceof ValidationException) {
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $exception->errors(),
        ], 422);
    }

    return parent::render($request, $exception);
}
}
