<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Webhook endpoint for receiving messages from poller
Route::post('/webhook', [WebhookController::class, 'receive']);

// Conversation APIs
Route::get('/conversations', [ConversationController::class, 'index']);
Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages']);
