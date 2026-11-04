<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PrestamoController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\RutaController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResource('clientes', ClienteController::class);

    Route::get('prestamos', [PrestamoController::class, 'index']);
    Route::post('prestamos', [PrestamoController::class, 'store']);
    Route::get('prestamos/{prestamo}', [PrestamoController::class, 'show']);
    Route::post('prestamos/{prestamo}/cancelar', [PrestamoController::class, 'cancelar']);
    Route::post('prestamos/{prestamo}/pagos', [PagoController::class, 'store']);

    Route::get('pagos', [PagoController::class, 'index']);

    Route::get('caja', [CajaController::class, 'index']);
    Route::post('caja', [CajaController::class, 'store']);

    Route::get('rutas', [RutaController::class, 'index']);
    Route::get('rutas/{ruta}', [RutaController::class, 'show']);

    Route::prefix('reportes')->group(function () {
        Route::get('resumen', [ReporteController::class, 'resumen']);
        Route::get('mora', [ReporteController::class, 'mora']);
        Route::get('cobro-del-dia', [ReporteController::class, 'cobroDelDia']);
        Route::get('periodo', [ReporteController::class, 'periodo']);
    });

    Route::middleware('admin')->group(function () {
        Route::apiResource('usuarios', UserController::class)->parameters(['usuarios' => 'user']);
        Route::post('rutas', [RutaController::class, 'store']);
        Route::match(['put', 'patch'], 'rutas/{ruta}', [RutaController::class, 'update']);
        Route::delete('rutas/{ruta}', [RutaController::class, 'destroy']);
    });
});
