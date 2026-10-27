<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Authentifie le compte visiteur unique et emet un token Sanctum.
     * Pas de session/cookie : le front stocke le token et le rejoue en
     * Authorization: Bearer sur les futures requetes protegees.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [
            'email' => config('visiteur.email'),
            'password' => $request->validated('password'),
        ];

        if (! Auth::once($credentials)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'token' => $user->createToken('visiteur-front')->plainTextToken,
        ]);
    }
}
