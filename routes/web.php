<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Backend API is running',
        'appName' => config('app.name'),
    ]);
});

// Contenu en dur, edite par les devs uniquement (pas de formulaire d'edition en ligne).
// Meme contenu que les widgets Filament du dashboard (resources/views/legal/).
Route::view('/mentions-legales', 'mentions-legales');
Route::view('/cgu', 'cgu');
