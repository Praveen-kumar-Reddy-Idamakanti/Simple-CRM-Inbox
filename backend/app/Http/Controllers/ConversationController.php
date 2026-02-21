<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    /**
     * Get list of conversations with optional search
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Conversation::with('contact')
                ->active()
                ->whereHas('contact', function($q) {
                    $q->where('name', 'not like', '[%]')
                      ->where('name', 'not like', '%User%');
                })
                ->orderBy('last_message_at', 'desc');

            // Apply search filter if provided
            if ($request->has('search')) {
                $searchTerm = $request->get('search');
                $query->whereHas('contact', function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            
            $conversations = $query->paginate($perPage, ['*'], 'page', $page);

            // Format the response
            $formattedConversations = $conversations->getCollection()->map(function ($conversation) {
                return [
                    'id' => $conversation->_id,
                    'contact_id' => $conversation->contact_id,
                    'contact_name' => $conversation->contact->name,
                    'contact_avatar' => $conversation->contact->avatar,
                    'channel' => $conversation->contact->channel,
                    'contact_email' => $conversation->contact->email,
                    'contact_phone' => $conversation->contact->phone,
                    'contact_metadata' => $conversation->contact->metadata,
                    'title' => $conversation->title,
                    'status' => $conversation->status,
                    'last_message_preview' => $conversation->last_message_preview,
                    'last_message_at' => $conversation->last_message_at,
                    'message_count' => $conversation->message_count,
                    'unread_count' => $conversation->unread_count,
                    'tags' => $conversation->contact->tags ?? [],
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ];
            });

            return response()->json([
                'data' => $formattedConversations,
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'from' => $conversations->firstItem(),
                    'to' => $conversations->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Conversation list error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to fetch conversations'
            ], 500);
        }
    }

    /**
     * Get messages for a specific conversation
     * 
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function messages(string $id, Request $request): JsonResponse
    {
        try {
            // Validate conversation exists
            $conversation = Conversation::with('contact')->find($id);
            
            if (!$conversation) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Conversation not found'
                ], 404);
            }

            // Build messages query
            $query = Message::where('conversation_id', $id)
                ->with('conversation.contact')
                ->orderBy('created_at', 'desc')
                ->notDeleted();

            // Pagination
            $perPage = $request->get('per_page', 50);
            $page = $request->get('page', 1);
            
            $messages = $query->paginate($perPage, ['*'], 'page', $page);

            // Format the response
            $formattedMessages = $messages->getCollection()->map(function ($message) {
                return [
                    'id' => $message->_id,
                    'conversation_id' => $message->conversation_id,
                    'sender_type' => $message->sender_type,
                    'sender_id' => $message->sender_id,
                    'text' => $message->text,
                    'message_type' => $message->message_type,
                    'formatted_text' => $message->formatted_text,
                    'attachments' => $message->attachments ?? [],
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ];
            });

            // Mark conversation as read (optional - for agent messages)
            if ($request->get('mark_as_read', false)) {
                $conversation->markAsRead();
            }

            return response()->json([
                'conversation' => [
                    'id' => $conversation->_id,
                    'contact_id' => $conversation->contact_id,
                    'contact_sender_id' => $conversation->contact->sender_id,
                    'contact_name' => $conversation->contact->name,
                    'contact_avatar' => $conversation->contact->avatar,
                    'contact_email' => $conversation->contact->email,
                    'contact_phone' => $conversation->contact->phone,
                    'contact_metadata' => $conversation->contact->metadata,
                    'channel' => $conversation->contact->channel,
                    'tags' => $conversation->contact->tags ?? [],
                    'title' => $conversation->title,
                    'status' => $conversation->status,
                ],
                'data' => $formattedMessages,
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'from' => $messages->firstItem(),
                    'to' => $messages->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Conversation messages error', [
                'conversation_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to fetch messages'
            ], 500);
        }
    }
}
