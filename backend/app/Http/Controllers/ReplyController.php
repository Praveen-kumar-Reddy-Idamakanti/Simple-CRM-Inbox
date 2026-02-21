<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Contact;
use App\Services\AiService;
use App\Services\MockServerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReplyController extends Controller
{
    protected $aiService;
    protected $mockServer;

    public function __construct(AiService $aiService, MockServerService $mockServer)
    {
        $this->aiService = $aiService;
        $this->mockServer = $mockServer;
    }

    /**
     * Send reply to a conversation (Manual Agent Reply)
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|string',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        try {
            $conversationId = $request->input('conversation_id');
            $messageText = $request->input('message');

            $conversation = Conversation::with('contact')->find($conversationId);
            if (!$conversation) {
                return response()->json(['error' => 'Not Found'], 404);
            }

            // Create agent message
            $message = Message::create([
                'conversation_id' => $conversation->_id,
                'sender_type' => 'agent',
                'sender_id' => 'agent_human',
                'text' => $messageText,
                'message_type' => 'text',
                'is_read' => true
            ]);

            $conversation->updateWithNewMessage($messageText, 'agent');

            // Send to mock server
            $sendResult = $this->mockServer->send($conversation->contact, $messageText);

            return response()->json([
                'status' => 'success',
                'message' => 'Reply sent successfully',
                'data' => [
                    'message_id' => $message->_id,
                    'text' => $messageText,
                    'sender_type' => 'agent',
                    'created_at' => $message->created_at,
                    'send_status' => $sendResult['status']
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Reply send error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
