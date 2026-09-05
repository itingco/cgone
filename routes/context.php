<?php

use App\Http\Controllers\DatabaseManagementController;
use App\Http\Controllers\DatabaseSwitchController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/context/database', DatabaseSwitchController::class)->name('database.switch');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profile/documents', [ProfileController::class, 'documents'])->name('profile.documents');

    Route::middleware('menu.permission:config.settings,edit')->prefix('configuration/databases')->group(function () {
        Route::get('/', [DatabaseManagementController::class, 'index'])->name('database.manager.index');
        Route::post('/test', [DatabaseManagementController::class, 'test'])->name('database.manager.test');
        Route::post('/register', [DatabaseManagementController::class, 'register'])->name('database.manager.register');
        Route::post('/create', [DatabaseManagementController::class, 'create'])->name('database.manager.create');
        Route::delete('/{database}', [DatabaseManagementController::class, 'unregister'])->name('database.manager.unregister');
    });
});
