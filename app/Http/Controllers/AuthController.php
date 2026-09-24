<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NewAccountRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'pending', // Queda pendiente de aprobación
        ]);

        // Notificar a todos los administradores
        $admins = User::role('Administrador')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewAccountRequestNotification($user));
        } else {
            // Fallback si no hay rol asignado, notificar al primer usuario o admin principal
            $fallbackAdmin = User::where('email', 'mantiaadso@gmail.com')->first();
            if ($fallbackAdmin) {
                $fallbackAdmin->notify(new NewAccountRequestNotification($user));
            }
        }

        return response()->json([
            'message' => 'Registro exitoso. Tu cuenta está pendiente de aprobación por un administrador.',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Validar si está aprobado
        if ($user->status === 'pending') {
            return response()->json(['message' => 'Tu cuenta aún está pendiente de aprobación por el administrador.'], 403);
        }

        if ($user->status === 'rejected') {
            return response()->json(['message' => 'Tu solicitud de cuenta ha sido rechazada.'], 403);
        }

        auth()->login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $code = rand(100000, 999999);

        // Guardar o actualizar el código en la tabla password_reset_tokens
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code),
                'created_at' => now()
            ]
        );

        // Enviar notificación con el código
        $user = User::where('email', $request->email)->first();
        $user->notify(new \App\Notifications\ResetCodeNotification($code));

        return response()->json(['message' => 'Se ha enviado un código de 6 dígitos a tu correo.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8',
        ]);

        $record = \DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->code, $record->token)) {
            return response()->json(['message' => 'El código de recuperación es inválido o ha expirado.'], 400);
        }

        // Actualizar contraseña
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Borrar el token usado
        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.']);
    }
}
