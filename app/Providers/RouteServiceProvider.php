<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // Compte connecté : l'écran fait plusieurs requêtes par action (8 pour déposer un vœu)
            if ($request->user()) {
                return Limit::perMinute(240)->by('compte:' . $request->user()->id);
            }
            // Sans compte (connexion, mot de passe oublié) : toute une salle peut partager
            // la même adresse (wifi, borne satellite), d'où une limite large par adresse.
            // Les essais de mot de passe restent bloqués par compte dans AuthController (5 échecs)
            return Limit::perMinute(300)->by('ip:' . $request->ip());
        });

        // Mot de passe oublié : il envoie un e-mail et change le mot de passe, on reste strict
        RateLimiter::for('forgot-password', function (Request $request) {
            return [
                Limit::perMinute(3)->by('forgot:' . Str::lower((string) $request->input('email'))),
                Limit::perMinute(30)->by('forgot-ip:' . $request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
