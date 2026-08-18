<?php

use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

/*Route::get('/', function () {
    return view('welcome');
});*/

Route::post('/ai/chat', [AiController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('ai.chat');
