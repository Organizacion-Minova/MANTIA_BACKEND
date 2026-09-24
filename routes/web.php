<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function ($token) {
    return redirect("http://localhost:5173/ResetPassword?token={$token}&email=" . request('email'));
})->name('password.reset');

Route::get('/test-mail', function () {
    try {
        \Mail::raw('Test SMTP MANTIA', function ($message) {
            $message->to('sebasmartinezzzz43@gmail.com')->subject('Test SMTP');
        });
        return 'Correo enviado OK! Revisá tu bandeja.';
    } catch (\Exception $e) {
        return 'Error: '.$e->getMessage();
    }
});