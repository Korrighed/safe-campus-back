<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HistoireController;
use App\Http\Controllers\SousThemeController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

// Authentification visiteur front : identifiants centralises en .env, un
// seul compte partage. Emet un token Sanctum, pas de session/cookie.
// 30 tentatives par minute et par IP (S-02) : le mot de passe est partage et les
// etudiants d'un meme evenement sortent par la meme IP (NAT du campus), une limite
// plus basse bloquerait des visiteurs legitimes. La longueur du mot de passe fait
// le reste.
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:30,1');

// Annuaire : lecture publique, sans authentification.
Route::get('themes', [ThemeController::class, 'index']);
Route::get('sous-themes/{sousTheme:ref}', [SousThemeController::class, 'show']);
Route::get('histoires/{histoire:ref}', [HistoireController::class, 'show']);
