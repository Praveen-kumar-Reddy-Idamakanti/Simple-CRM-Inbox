<?php

namespace App\Http\Controllers;

use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Receive webhook payload from poller
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function receive(Request $request): JsonResponse
    {
        // Validate webhook token
        $expectedToken = 'secret123';
        $providedToken = $request->header('X-Webhook-Token');

        if (!$providedToken || $providedToken !== $expectedToken) {
            Log::warning('Invalid webhook token', [
                'provided_token' => $providedToken,
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid webhook token'
            ], 401);
        }

        // Validate JSON payload
        if (!$request->getContent() || !json_decode($request->getContent())) {
            Log::error('Invalid JSON payload received', [
                'content_type' => $request->header('Content-Type'),
                'content' => $request->getContent(),
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Invalid JSON payload'
            ], 400);
        }

        try {
            $payload = $request->json()->all();
            
            // Log incoming webhook for debugging
            Log::info('Webhook received', [
                'payload_keys' => array_keys($payload),
                'ip' => $request->ip()
            ]);

            // Process the webhook payload
            $this->webhookService->processWebhook($payload);

            return response()->json([
                'status' => 'ok',
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->getContent()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to process webhook'
            ], 500);
        }
    }
}
