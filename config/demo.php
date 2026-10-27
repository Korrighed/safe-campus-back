<?php

/*
 * Contenu de demonstration en production (histoires, medias de demo).
 *
 * Les seeders de demo sont ignores quand APP_ENV=production. DEMO_CONTENT=true
 * les autorise : voulu pour une demo publique, jamais pour la production reelle.
 * Sur un hebergeur au disque ephemere (Render, offre gratuite), docker/8.4/start-prod
 * rejoue ces seeders a chaque demarrage pour recreer les fichiers de media.
 */
return [
    'content' => filter_var(env('DEMO_CONTENT', false), FILTER_VALIDATE_BOOLEAN),
];
