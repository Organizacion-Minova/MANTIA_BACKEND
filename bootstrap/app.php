<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
        ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->appendToGroup('api', \App\Http\Middleware\EnsureAccountIsActive::class);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Sesión vencida o sin iniciar
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Tu sesión expiró. Inicia sesión de nuevo.'], 401);
            }
        });

        // Errores HTTP del framework y de Spatie, que llegan en inglés
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $mensajes = [
                403 => 'No tienes permiso para realizar esta acción.',
                404 => 'No se encontró lo que buscas.',
                405 => 'Esta acción no está permitida.',
                419 => 'Tu sesión expiró. Recarga la página e inténtalo de nuevo.',
                429 => 'Demasiados intentos. Espera un momento e inténtalo de nuevo.',
            ];

            $estado = $e->getStatusCode();
            if (! isset($mensajes[$estado])) {
                return null;
            }

            // Si el mensaje ya es uno propio en español, se deja tal cual
            $texto = $e->getMessage();
            $esDelFramework = $texto === '' || preg_match(
                '/^(No query results|The route |Not Found|This action is unauthorized|User does not have|CSRF token mismatch|Too Many Attempts|Forbidden|Method Not Allowed|The \w+ method is not supported)/i',
                $texto
            );

            if (! $esDelFramework) {
                return null;
            }

            return response()->json(['message' => $mensajes[$estado]], $estado, $e->getHeaders());
        });
    })->create();