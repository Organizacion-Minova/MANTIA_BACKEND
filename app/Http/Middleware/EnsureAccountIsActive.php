<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Se ejecuta en cada petición de la API. Si el usuario fue desactivado, o si todos sus roles
 * están desactivados, se le cierra la sesión en el acto aunque ya estuviera conectado.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // Estas rutas deben funcionar aunque la cuenta esté desactivada:
        // entrar con otra cuenta y cerrar sesión.
        if ($request->is('api/login', 'api/logout', 'api/register', 'api/forgot-password', 'api/reset-password', 'api/dev-login/*', 'api/email/verify/*')) {
            return $next($request);
        }

        $user = $request->user('sanctum');

        if ($user instanceof User) {
            $motivo = $this->motivoBloqueo($user);

            if ($motivo) {
                Auth::guard('web')->logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return response()->json(['message' => $motivo, 'codigo' => 'cuenta_inactiva'], 401);
            }
        }

        return $next($request);
    }

    private function motivoBloqueo(User $user): ?string
    {
        if (in_array($user->status, ['inactive', 'rejected', 'pending'], true) || $user->is_active === false) {
            return 'Tu cuenta está desactivada. Comunícate con el administrador.';
        }

        // Tiene roles, pero ninguno está activo
        if ($user->roles()->exists() && ! $user->roles()->where('roles.is_active', true)->exists()) {
            return 'Tu rol está desactivado. Comunícate con el administrador.';
        }

        return null;
    }
}
