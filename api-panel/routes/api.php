<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\User\userController;

// --- Rutas de Autenticación (JWT) ---
Route::group([
    'prefix' => 'auth'
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
});

use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;

// --- Rutas de Roles y Usuarios (Base Limpia) ---
Route::group([
    // 'middleware' => ['auth:api']
], function ($router) {
    Route::resource('role', RoleController::class);

    Route::get('user/get-roles', [userController::class, 'getRoles']);
    Route::post('user/{id}', [userController::class, 'update']);
    Route::resource('user', userController::class);

    // Dashboard Stats
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // Clientes
    Route::resource('clients', ClientController::class);

    // Pedidos
    Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::resource('orders', OrderController::class);
});