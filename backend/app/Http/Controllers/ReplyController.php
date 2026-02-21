<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ReplyController extends Controller
{
    /**
     * Send reply to a conversation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|string',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conversationId = $request->input('conversation_id');
            $messageText = $request->input('message');

            // Find conversation
            $conversation = Conversation::with('contact')->find($conversationId);
            
            if (!$conversation) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Conversation not found'
                ], 404);
            }

            // Create agent message
            $message = Message::create([
                'conversation_id' => $conversation->_id,
                'sender_type' => 'agent',
                'sender_id' => 'agent_system', // Can be enhanced to track actual agent
                'text' => $messageText,
                'message_type' => 'text',
                'is_read' => true // Agent messages are marked as read
            ]);

            // Update conversation with new message details
            if (method_exists($conversation, 'updateWithNewMessage')) {
                $conversation->updateWithNewMessage($messageText, 'agent');
            } else {
                // Fallback if method doesn't exist
                $conversation->last_message_preview = $messageText;
                $conversation->last_message_at = now();
                $conversation->last_message_sender = 'agent';
                $conversation->save();
            }

            // Send message to mock server
            $sendResult = $this->sendToMockServer($conversation->contact, $messageText);

            // Format response
            $response = [
                'status' => 'success',
                'message' => 'Reply sent successfully',
                'data' => [
                    'message_id' => $message->_id,
                    'conversation_id' => $conversation->_id,
                    'text' => $messageText,
                    'sender_type' => 'agent',
                    'created_at' => $message->created_at,
                    'send_status' => $sendResult['status'],
                    'send_response' => $sendResult['response']
                ]
            ];

            Log::info('Agent reply sent', [
                'conversation_id' => $conversationId,
                'message_text' => substr($messageText, 0, 100),
                'send_status' => $sendResult['status']
            ]);

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Reply send error', [
                'conversation_id' => $request->input('conversation_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to send reply'
            ], 500);
        }
    }

    /**
     * Send message to mock server
     * 
     * @param Contact $contact
     * @param string $message
     * @return array
     */
    private function sendToMockServer(Contact $contact, string $message): array
    {
        try {
            $apiKey = config('services.mock.api_key', env('API_KEY'));
            
            if (!$apiKey) {
                Log::warning('No API key configured for mock server');
                return [
                    'status' => 'skipped',
                    'http_status' => 0,
                    'response' => ['message' => 'API key not configured'],
                    'sent_at' => now()->toISOString()
                ];
            }
            
            $mockServerUrl = 'https://mock-simulation.omts.in/send';

            $payload = [
                'sender_id' => $contact->sender_id,
                'message' => $message
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($mockServerUrl, $payload);

            $result = [
                'status' => $response->successful() ? 'success' : 'failed',
                'http_status' => $response->status(),
                'response' => $response->json(),
                'sent_at' => now()->toISOString()
            ];

            if (!$response->successful()) {
                Log::warning('Mock server send failed', [
                    'contact_id' => $contact->_id,
                    'sender_id' => $contact->sender_id,
                    'http_status' => $response->status(),
                    'response' => $response->json()
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Mock server send error', [
                'contact_id' => $contact->_id,
                'sender_id' => $contact->sender_id,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'http_status' => 0,
                'response' => ['error' => $e->getMessage()],
                'sent_at' => now()->toISOString()
            ];
        }
    }
}
