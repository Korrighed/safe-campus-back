<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Derriere un reverse proxy qui termine le TLS (hebergeur), Laravel croit
        // sinon etre en HTTP : URL generees en http://, IP du proxy dans le journal
        // d'audit et dans la limite de debit. TRUSTED_PROXIES : liste d'IP/CIDR
        // separees par des virgules, ou `*` si le conteneur n'est joignable que par
        // le proxy. Vide = aucun proxy de confiance (valeur par defaut).
        $proxies = trim((string) env('TRUSTED_PROXIES', ''));

        if ($proxies !== '') {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
