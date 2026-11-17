<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// El backend es solo API: la página la muestra el frontend
Route::get('/', fn () => response()->json(['app' => 'MANTIA API', 'estado' => 'ok']));

Route::get('/reset-password/{token}', function ($token) {
    $front = rtrim(config('app.frontend_url'), '/');

    return redirect("{$front}/ResetPassword?token=" . urlencode($token) . '&email=' . urlencode((string) request('email')));
})->name('password.reset');

// Prueba de correo: solo en local y hacia el correo configurado como remitente
if (app()->environment('local')) {
    Route::get('/test-mail', function () {
        try {
            Mail::raw('Prueba de correo MANTIA', function ($message) {
                $message->to(config('mail.from.address'))->subject('Prueba de correo');
            });

            return 'Correo enviado. Si MAIL_MAILER=log, búscalo en storage/logs/laravel.log';
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }
    });
}
