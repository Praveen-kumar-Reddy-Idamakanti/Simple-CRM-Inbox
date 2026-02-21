<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MockServerService
{
    /**
     * Send message to mock server
     */
    public function send(Contact $contact, string $message): array
    {
        try {
            $apiKey = config('services.mock.api_key', env('API_KEY'));
            
            if (!$apiKey) {
                Log::warning('No API key configured for mock server');
                return [
                    'status' => 'skipped',
                    'response' => ['message' => 'API key missing']
                ];
            }
            
            $mockServerUrl = 'https://mock-simulation.omts.in/send';

            $response = Http::retry(5, 100)->timeout(15)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                    'User-Agent' => 'python-requests/2.31.0',
                    'Accept' => 'application/json',
                    'Connection' => 'close'
                ])
                ->withOptions([
                    'verify' => false,
                ])
                ->post($mockServerUrl, [
                    'sender_id' => $contact->sender_id,
                    'message' => $message
                ]);

            if (!$response->successful()) {
                 Log::warning('Mock server delivery failed', [
                    'status' => $response->status(),
                    'sender_id' => $contact->sender_id
                ]);
            }

            return [
                'status' => $response->successful() ? 'success' : 'failed',
                'http_status' => $response->status(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Mock server send error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'response' => ['error' => $e->getMessage()]
            ];
        }
    }
}
