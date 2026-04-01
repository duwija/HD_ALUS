<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProbeController;
use App\Http\Controllers\WhatsappController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

// route default bawaan Laravel
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// ========================
// Tambahkan route probe di luar blok di atas
// ========================

Route::get ('/probe/push',  [ProbeController::class, 'push']);     // Mikrotik (GET)
Route::post('/probe/push',  [ProbeController::class, 'pushPost']); // JSON probe (POST)
Route::get ('/probe/status',[ProbeController::class, 'status']);   // API status
Route::get ('/probe/alerts',[ProbeController::class, 'alerts']);   // API alert log
Route::post('/wa/{session}/send-media', [WhatsappController::class, 'sendMedia']);

