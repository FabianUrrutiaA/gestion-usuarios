<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferenciaController;
use App\Http\Controllers\UserController;

Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    //Usuarios

    Route::get('/obtenerUsuarios', [UserController::class, 'obtenerUsuarios']);
    Route::get('/obtenerUsuario/{id}', [UserController::class, 'obtenerUsuario']);
    Route::post('/crearUsuario', [UserController::class, 'crearUsuario']);
    Route::put('/editarUsuario/{id}', [UserController::class, 'editarUsuario']);
    Route::delete('/eliminarUsuario/{id}', [UserController::class, 'eliminarUsuario']);


    //Transferencias
    Route::post('/crearTransferencia', [TransferenciaController::class, 'crearTransferencia']);
    Route::get('/exportarTransferenciasCSV', [TransferenciaController::class, 'exportarCSV']);
    Route::get('/totalTransferidoPorUsuario', [TransferenciaController::class, 'totalTransferidoPorUsuario']);
    Route::get('/promedioMontoPorUsuario', [TransferenciaController::class, 'promedioMontoPorUsuario']);

});