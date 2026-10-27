<?php

/*
 * CORS de l'API (S-09). Seul le navigateur du site etudiant appelle l'API
 * depuis un autre domaine ; le SSR de Nuxt appelle le back cote serveur, hors
 * CORS. Les origines autorisees viennent de CORS_ALLOWED_ORIGINS (liste
 * separee par des virgules). Le defaut est le front de dev : en production la
 * variable est obligatoire dans docker-compose.prod.yml.
 *
 * Pas de cookies cross-origin (`supports_credentials` a false) : l'API utilise
 * un jeton Bearer.
 */
return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => false,
];
