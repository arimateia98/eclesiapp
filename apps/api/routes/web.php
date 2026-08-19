<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\ActiveParishController;
use App\Modules\Identity\Http\Controllers\GoogleSessionController;
use App\Modules\Identity\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:5,1')->name('login');
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/auth/google/redirect', [GoogleSessionController::class, 'redirect'])->middleware('throttle:10,1')->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleSessionController::class, 'callback'])->middleware('throttle:10,1')->name('auth.google.callback');

Route::middleware('auth')->prefix('api/v1/session')->group(function (): void {
    Route::put('/active-parish', [ActiveParishController::class, 'store'])->name('api.v1.session.active-parish.store');
    Route::delete('/active-parish', [ActiveParishController::class, 'destroy'])->name('api.v1.session.active-parish.destroy');
});
