<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @group Gestión de Usuarios
 * 
 * APIs para gestionar usuarios del sistema
 */

class UserController extends Controller
{
 
    /**
     * Login de usuario
     * 
     * Autentica un usuario y retorna un token de acceso Bearer.
     * 
     * @bodyParam email string required El email del usuario. Example: juan@example.com
     * @bodyParam password string required La contraseña del usuario. Example: password123
     * 
     * @response 200 {
     *   "access_token": "1|abc123xyz...",
     *   "token_type": "Bearer",
     *   "expires_in": 3600,
     *   "user": {
     *     "id": 1,
     *     "name": "Juan Pérez",
     *     "email": "juan@example.com",
     *     "saldo": "1000.00"
     *   }
     * }
     * 
     * @response 401 {
     *   "message": "Credenciales incorrectas"
     * }
     */

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 200);
    }

    /**
     * Obtener todos los usuarios
     * 
     * Retorna un listado de todos los usuarios registrados.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "usuarios": [
     *     {
     *       "id": 1,
     *       "name": "Juan Pérez",
     *       "email": "juan@example.com",
     *       "saldo": "1000.00",
     *       "created_at": "2026-01-07T00:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function obtenerUsuarios() 
    {
        $usuarios = User::all();

        return response()->json([
            'usuarios' => $usuarios
        ], 200);
    }

    /**
     * Obtener usuario por ID
     * 
     * Retorna los detalles de un usuario específico.
     * 
     * @authenticated
     * 
     * @urlParam id integer required ID del usuario. Example: 1
     * 
     * @response 200 {
     *   "usuario": {
     *     "id": 1,
     *     "name": "Juan Pérez",
     *     "email": "juan@example.com",
     *     "saldo": "1000.00"
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Usuario no encontrado"
     * }
     */
    public function obtenerUsuario($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'user' => $user
        ], 200);
    }

    /**
     * Crear nuevo usuario
     * 
     * Registra un nuevo usuario en el sistema.
     * 
     * @authenticated
     * 
     * @bodyParam name string required Nombre completo del usuario. Example: Juan Pérez
     * @bodyParam email string required Email único del usuario. Example: juan@example.com
     * @bodyParam password string required Contraseña (mínimo 8 caracteres). Example: password123
     * @bodyParam saldo numeric optional Saldo inicial del usuario. Example: 1000.50
     * 
     * @response 201 {
     *   "message": "Usuario creado exitosamente",
     *   "user": {
     *     "id": 5,
     *     "name": "Juan Pérez",
     *     "email": "juan@example.com",
     *     "saldo": "1000.50"
     *   }
     * }
     * 
     * @response 422 {
     *   "errors": {
     *     "email": ["El email ya está registrado"]
     *   }
     * }
     */
    public function crearUsuario(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'saldo' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'saldo' => $request->saldo ?? 0.00
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user
        ], 201);
    }

    /**
     * Actualizar usuario
     * 
     * Actualiza la información de un usuario existente.
     * 
     * @authenticated
     * 
     * @urlParam id integer required ID del usuario a actualizar. Example: 1
     * @bodyParam name string optional Nuevo nombre del usuario. Example: Juan Pérez Actualizado
     * @bodyParam email string optional Nuevo email del usuario. Example: nuevo@example.com
     * @bodyParam password string optional Nueva contraseña. Example: newpassword123
     * @bodyParam saldo numeric optional Nuevo saldo. Example: 2000.00
     * 
     * @response 200 {
     *   "message": "Usuario actualizado exitosamente",
     *   "user": {
     *     "id": 1,
     *     "name": "Juan Pérez Actualizado",
     *     "email": "nuevo@example.com",
     *     "saldo": "2000.00"
     *   }
     * }
     */
    public function editarUsuario(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'saldo' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->has('saldo')) {
            $user->saldo = $request->saldo;
        }

        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user
        ], 200);
    }

    /**
     * Eliminar usuario
     * 
     * Elimina permanentemente un usuario del sistema.
     * 
     * @authenticated
     * 
     * @urlParam id integer required ID del usuario a eliminar. Example: 1
     * 
     * @response 200 {
     *   "message": "Usuario eliminado exitosamente"
     * }
     * 
     * @response 404 {
     *   "message": "Usuario no encontrado"
     * }
     */
    public function eliminarUsuario($id)
    {

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente'
        ], 200);   
    }
}
