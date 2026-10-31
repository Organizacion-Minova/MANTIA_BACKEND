<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Datos personales y foto del propio usuario. El correo y el rol no se editan aquí.
    public function update(Request $request)
    {
        $user = $request->user();

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'document_type' => 'required|in:CC,CE,TI,PA',
            'document_number' => ['required', 'string', 'max:20', Rule::unique('users', 'document_number')->ignore($user->id)],
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'document_type.in' => 'El tipo de documento no es válido.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique' => 'Ese número de documento ya está registrado.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'photo.max' => 'La foto no puede superar los 2 MB.',
        ]);

        $user->name = $datos['name'];
        $user->last_name = $datos['last_name'];
        $user->document_type = $datos['document_type'];
        $user->document_number = $datos['document_number'];

        if ($request->hasFile('photo')) {
            $nueva = $request->file('photo')->store('avatars', 'public');
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->photo_path = $nueva;
        } elseif ($request->boolean('remove_photo') && $user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->photo_path = null;
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user->fresh(),
        ]);
    }

    // Cambio de contraseña: exige la contraseña actual.
    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Escribe tu contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Escribe la nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
            'password.different' => 'La nueva contraseña debe ser diferente a la actual.',
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        // Cierra las demás sesiones abiertas de esta cuenta (si las sesiones están en la base de datos)
        try {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
