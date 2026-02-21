<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get AI suggested reply for a conversation
     */
    public function suggest(string $id): JsonResponse
    {
        try {
            $conversation = Conversation::find($id);
            if (!$conversation) {
                return response()->json(['error' => 'Not Found'], 404);
            }

            $lastContactMessage = Message::where('conversation_id', $id)
                ->where('sender_type', 'contact')
                ->latest()
                ->first();

            $context = $lastContactMessage ? $lastContactMessage->text : "Hello";
            $suggestion = $this->aiService->getSuggestion($conversation, $context);

            return response()->json([
                'status' => 'success',
                'suggestion' => $suggestion,
            ]);

        } catch (\Exception $e) {
            Log::error("AI suggest error: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
