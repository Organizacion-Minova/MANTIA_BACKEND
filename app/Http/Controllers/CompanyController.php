<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Database\QueryException;

class CompanyController extends Controller
{
    public function index()
    {
        $empresas = Company::all();
        return response()->json($empresas, 200);
    }

    public function store(Request $request)
    {
        $datosValidados = $request->validate([
            'nit' => 'required|string|max:20|unique:company,nit|regex:/^\d{1,3}(?:\.?\d{3})*(?:-?\d)?$/',
            'nombre' => 'required|string|max:55',
            'telefono' => 'required|string|max:20|unique:company,phone|regex:/^\+?[0-9\s\-()]+$/',
            'correo' => 'required|email|max:100|unique:company,email',
            'direccion' => 'required|string|max:100',
        ], [
            'nit.max' => 'El NIT no puede superar los 20 caracteres.',
            'nit.unique' => 'Ya existe una empresa registrada con este NIT.',
            'nit.regex' => 'Ingrese un NIT válido.',
            'nombre.max' => 'El nombre no puede superar los 55 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
            'telefono.unique' => 'Ya existe una empresa registrada con este teléfono .',
            'telefono.regex' => 'Ingrese un teléfono válido.',
            'correo.email' => 'Ingrese un correo electrónico válido.',
            'correo.max' => 'El correo no puede superar los 100 caracteres.',
            'correo.unique' => 'Ya existe una empresa registrada con este correo electrónico.',
            'direccion.max' => 'La dirección no puede superar los 100 caracteres.',
        ]);

        $empresa = Company::create([
            'nit' => $datosValidados['nit'],
            'name' => $datosValidados['nombre'],
            'phone' => $datosValidados['telefono'],
            'email' => $datosValidados['correo'],
            'address' => $datosValidados['direccion'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa registrada correctamente.',
            'data' => $empresa,
        ], 201);
    }

    public function update(Request $request, $tax_id)
    {
        $empresa = Company::findOrFail($tax_id);

        $datosValidados = $request->validate([
            'nombre' => 'required|string|max:55',
            'telefono' => 'required|string|max:20|unique:company,phone,|regex:/^\+?[0-9\s\-()]+$/' . $tax_id . ',tax_id',
            'correo' => 'required|email|max:100|unique:company,email,' . $tax_id . ',tax_id',
            'direccion' => 'required|string|max:100',
        ], [
            'nombre.max' => 'El nombre no puede superar los 55 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
            'telefono.unique' => 'Ya existe una empresa registrada con este teléfono.',
            'telefono.regex' => 'Ingrese un teléfono válido.',
            'correo.email' => 'Ingrese un correo electrónico válido.',
            'correo.max' => 'El correo no puede superar los 100 caracteres.',
            'correo.unique' => 'Ya existe una empresa registrada con este correo electrónico.',
            'direccion.max' => 'La dirección no puede superar los 100 caracteres.',
        ]);

        $empresa->update([
            'name' => $datosValidados['nombre'],
            'phone' => $datosValidados['telefono'],
            'email' => $datosValidados['correo'],
            'address' => $datosValidados['direccion'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa actualizada correctamente.',
            'data' => $empresa,
        ], 200);
    }

    public function destroy($tax_id)
    {
        try {
            $empresa = Company::findOrFail($tax_id);
            $empresa->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Empresa eliminada correctamente.'
            ], 200);

        } catch (QueryException $e) {
            if ($e->getCode() === '23503' || str_contains($e->getMessage(), 'foreign key constraint')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede eliminar la empresa porque tiene equipos o maquinas asociadas.'
                ], 422); 
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la empresa.'
            ], 500);
        }
    }
}