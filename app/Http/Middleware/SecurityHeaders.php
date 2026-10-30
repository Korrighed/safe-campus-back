<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-tetes de securite poses par l'application, en complement de ceux
 * du reverse proxy eventuel.
 *
 * - API JSON (`api/*`) : aucune ressource a charger, donc `default-src 'none'`.
 * - Reste (panneau Filament, pages legales) : seul `frame-ancestors` est pose.
 *   Une CSP de scripts stricte est impossible tant que Livewire/Alpine exigent
 *   `unsafe-eval` : a traiter a part.
 * - HSTS uniquement en HTTPS (derriere un proxy : voir TRUSTED_PROXIES).
 *
 * Les fichiers servis directement par le serveur web (public/storage) ne
 * passent pas par Laravel et n'ont pas ces en-tetes.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            $request->is('api/*') ? "default-src 'none'; frame-ancestors 'none'" : "frame-ancestors 'none'",
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
