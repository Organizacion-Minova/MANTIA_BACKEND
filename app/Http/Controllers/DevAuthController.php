<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevAuthController extends Controller
{
    /**
     * Login automático por letra — SOLO existe en entorno local.
     * La ruta que llama a este método ya está protegida con el mismo
     * chequeo de entorno, pero lo repetimos aquí como segunda barrera:
     * si alguien copia esta lógica a otro lado, sigue sin funcionar
     * fuera de local.
     */
    public function quickLogin(string $letra, Request $request)
    {
        abort_unless(app()->environment('local'), 404);

        $user = User::where('dev_letter', strtoupper($letra))->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user]);
    }
}