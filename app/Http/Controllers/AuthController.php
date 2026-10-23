<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NewAccountRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'document_type' => 'required|in:CC,CE,TI,PA',
            'document_number' => 'required|string|max:20|unique:users',
            'requested_role' => 'required|in:Administrador,Invitado,Tecnico',
            'technician_type' => 'required_if:requested_role,Tecnico|nullable|in:Electricista,Minero,Mecanico',
        ]);

        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'pending',
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'requested_role' => $request->requested_role,
            'technician_type' => $request->requested_role === 'Tecnico' ? $request->technician_type : null,
        ]);

        // Notificar a todos los administradores.
        // Si el correo falla, el registro ya quedó guardado: no se debe devolver error.
        try {
            $admins = User::role('Administrador')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewAccountRequestNotification($user));
            } else {
                $fallbackAdmin = User::where('email', 'mantiaadso@gmail.com')->first();
                if ($fallbackAdmin) {
                    $fallbackAdmin->notify(new NewAccountRequestNotification($user));
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'Registro exitoso. Tu cuenta está pendiente de aprobación',
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

        Auth::login($user);
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
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code),
                'created_at' => now()
            ]
        );

        // Enviar notificación con el código
        $user = User::where('email', $request->email)->first();

        try {
            $user->notify(new \App\Notifications\ResetCodeNotification($code));
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudo enviar el correo con el código. Intenta de nuevo en unos minutos.',
            ], 503);
        }

        return response()->json(['message' => 'Se ha enviado un código de 6 dígitos a tu correo.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->code, $record->token)) {
            return response()->json(['message' => 'El código de recuperación es inválido o ha expirado.'], 400);
        }

        // Actualizar contraseña
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Borrar el token usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.']);
    }
}