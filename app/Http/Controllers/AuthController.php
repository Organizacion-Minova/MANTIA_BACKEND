<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NewAccountRequestNotification;
use App\Notifications\ResetCodeNotification;
use App\Notifications\VerifyAccountNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        // Libera correos y documentos de cuentas que nunca se verificaron
        User::whereNull('email_verified_at')
            ->whereNotNull('verification_expires_at')
            ->where('verification_expires_at', '<', now())
            ->delete();

        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc|max:255|unique:users',
            'password' => 'required|string|min:8',
            'document_type' => 'required|in:CC,CE,TI,PA',
            'document_number' => 'required|string|max:20|unique:users',
        ]);

        // El rol NUNCA viene del formulario: siempre Visitante.
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'pending',
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
        ]);
        $user->verification_expires_at = now()->addHours(24);
        $user->save();
        $user->assignRole('Visitante');

        try {
            $user->notify(new VerifyAccountNotification());
        } catch (\Throwable $e) {
            report($e);
            $user->delete();

            return response()->json([
                'message' => 'No se pudo enviar el correo de verificación. Intenta de nuevo en unos minutos.',
            ], 503);
        }

        return response()->json([
            'message' => 'Registro exitoso. Revisa tu correo y abre el enlace para activar tu cuenta (dura 24 horas).',
        ], 201);
    }

    // Enlace del correo: activa la cuenta y avisa al Jefe de mina.
    public function verifyEmail(Request $request, $id)
    {
        $front = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $user = User::find($id);

        if (! $user || ! $user->verification_expires_at) {
            return redirect("{$front}/login?verificado=0");
        }

        if ($user->verification_expires_at->isPast()) {
            return redirect("{$front}/login?verificado=expirado");
        }

        $user->email_verified_at = now();
        $user->status = 'approved';
        $user->verification_expires_at = null;
        $user->save();

        try {
            $jefes = User::role('Jefe de mina')->get();
            if ($jefes->isNotEmpty()) {
                Notification::send($jefes, new NewAccountRequestNotification($user));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect("{$front}/login?verificado=1");
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
            $mensaje = $user->verification_expires_at
                ? 'Debes verificar tu correo. Revisa tu bandeja y abre el enlace.'
                : 'Tu cuenta aún está pendiente de aprobación por el administrador.';

            return response()->json(['message' => $mensaje], 403);
        }

        if ($user->status === 'rejected') {
            return response()->json(['message' => 'Tu solicitud de cuenta ha sido rechazada.'], 403);
        }

        if ($user->status === 'inactive' || ($user->is_active !== null && ! $user->is_active)) {
            return response()->json(['message' => 'Tu cuenta está desactivada. Comunícate con el administrador.'], 403);
        }

        // Tiene roles, pero todos están desactivados
        if ($user->roles()->exists() && ! $user->roles()->where('roles.is_active', true)->exists()) {
            return response()->json(['message' => 'Tu rol está desactivado. Comunícate con el administrador.'], 403);
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
        // Sin "exists": así nadie puede descubrir qué correos están registrados.
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make((string) $code),
                    'created_at' => now(),
                ]
            );
            Cache::forget('reset_intentos:' . $request->email);

            try {
                $user->notify(new ResetCodeNotification($code));
            } catch (\Throwable $e) {
                report($e); // queda en storage/logs; al usuario se le responde igual
            }
        }

        // Misma respuesta exista o no el correo
        return response()->json(['message' => 'Si el correo está registrado, te enviamos un código de 6 dígitos.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8',
        ]);

        $email = $request->email;
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        // El código dura 15 minutos
        if ($record && now()->diffInMinutes($record->created_at, true) > 15) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            $record = null;
        }

        if (! $record) {
            return response()->json(['message' => 'El código de recuperación es inválido o ha expirado.'], 400);
        }

        // Máximo 5 intentos por código: al quinto error el código se invalida
        $clave = 'reset_intentos:' . $email;

        if (! Hash::check($request->code, $record->token)) {
            Cache::add($clave, 0, now()->addMinutes(15)); // crea el contador si no existe
            $intentos = (int) Cache::increment($clave);

            if ($intentos >= 5) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                Cache::forget($clave);
            }

            return response()->json(['message' => 'El código de recuperación es inválido o ha expirado.'], 400);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['message' => 'El código de recuperación es inválido o ha expirado.'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        Cache::forget($clave);

        return response()->json(['message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.']);
    }
}