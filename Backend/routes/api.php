<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Api\TicketFromWaController;
use App\Http\Controllers\Api\WaHelpController;
use App\Http\Controllers\Api\WaNotificationController;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Route;

//route wa to API tidak membutuhkan role auth
Route::post('/tickets/wa', [TicketFromWaController::class, 'store']);
Route::get('/wa/ticket-status/{code}', [TicketController::class, 'waStatus']);
Route::post('/wa/help', [WaHelpController::class, 'checkAndTouch']);
Route::post('/wa/my-tickets', [TicketController::class, 'waMyTickets']);

//route user login website
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

//notifikasi whatsapp otomatis
Route::get('/wa/pending-notifications', [WaNotificationController::class, 'pending']);
Route::post('/wa/notification-sent/{id}', [WaNotificationController::class, 'markSent'])
    ->whereNumber('id');


//route middleware auth:scantum (harus memiliki role)
Route::middleware('auth:sanctum')->group(function(){
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistic']);

    Route::get('/ticket', [TicketController::class, 'index']);
    Route::get('/ticket/{code}', [TicketController::class, 'show']);
    Route::post('/ticket', [TicketController::class, 'store']);
    Route::post('/ticket-reply/{code}', [TicketController::class, 'storeReply']);
});