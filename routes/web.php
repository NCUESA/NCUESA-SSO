<?php

use App\Http\Controllers\AuthController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {
    return view('login'); 
})->name('login');

Route::get('/auth/{provider}', [AuthController::class, 'redirectToProvider'])
    ->where('provider', 'google|microsoft');

Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])
    ->where('provider', 'google|microsoft');

Route::get('/oauth/authorize', [AuthorizationController::class, 'authorize'])
    ->middleware('auth'); // 加上 middleware
    
