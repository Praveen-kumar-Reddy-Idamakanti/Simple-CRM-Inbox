<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookService
{
    /**
     * Process incoming webhook payload
     * 
     * @param array $payload
     * @return void
     * @throws Exception
     */
    public function processWebhook(array $payload): void
    {
        // Extract message information from payload
        $messageInfo = $this->extractMessageInfo($payload);
        
        if (!$messageInfo) {
            Log::warning('No message found in webhook payload', ['payload' => $payload]);
            return;
        }

        Log::info('Processing message', [
            'sender_id' => $messageInfo['sender_id'],
            'channel' => $messageInfo['channel'],
            'text' => substr($messageInfo['text'], 0, 100)
        ]);

        // Ensure contact and conversation exist
        $this->ensureContactAndConversation($payload, $messageInfo);
    }

    /**
     * Extract message information from webhook payload
     * 
     * @param array $payload
     * @return array|null
     */
    private function extractMessageInfo(array $payload): ?array
    {
        try {
            $channel = $payload['object'] ?? 'unknown';
            
            $entries = $payload['entry'] ?? [];
            if (empty($entries) || !is_array($entries)) {
                return null;
            }

            $firstEntry = $entries[0];
            $messagingList = $firstEntry['messaging'] ?? [];
            
            if (empty($messagingList) || !is_array($messagingList)) {
                return null;
            }

            $messaging = $messagingList[0];
            $sender = $messaging['sender'] ?? [];
            $message = $messaging['message'] ?? [];

            return [
                'channel' => $channel,
                'sender_id' => $sender['id'] ?? null,
                'text' => $message['text'] ?? '',
                'message_type' => $this->detectMessageType($message),
                'raw_payload' => $payload
            ];

        } catch (Exception $e) {
            Log::error('Failed to extract message info', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return null;
        }
    }

    /**
     * Detect message type from message payload
     * 
     * @param array $message
     * @return string
     */
    private function detectMessageType(array $message): string
    {
        if (isset($message['attachments']) && !empty($message['attachments'])) {
            $attachment = $message['attachments'][0] ?? [];
            $type = $attachment['type'] ?? 'unknown';
            
            return match ($type) {
                'image' => 'image',
                'file' => 'file',
                'audio' => 'audio',
                'video' => 'video',
                default => 'attachment'
            };
        }

        return 'text';
    }

    /**
     * Ensure contact and conversation exist, create message
     * 
     * @param array $payload
     * @param array $messageInfo
     * @return void
     * @throws Exception
     */
    private function ensureContactAndConversation(array $payload, array $messageInfo): void
    {
        $senderId = $messageInfo['sender_id'];
        $channel = $messageInfo['channel'];
        
        if (!$senderId) {
            throw new Exception('Sender ID is required');
        }

        // Find or create contact
        $contact = $this->findOrCreateContact($senderId, $channel);
        
        // Find or create conversation
        $conversation = $this->findOrCreateConversation($contact);
        
        // Create message
        $this->createMessage($conversation, $messageInfo);
        
        // Update conversation with new message details
        $conversation->updateWithNewMessage($messageInfo['text'], 'contact');
        
        // Update contact's last seen
        $contact->updateLastSeen();

        Log::info('Message processed successfully', [
            'contact_id' => $contact->_id,
            'conversation_id' => $conversation->_id,
            'sender_id' => $senderId
        ]);
    }

    /**
     * Find or create contact
     * 
     * @param string $senderId
     * @param string $channel
     * @return Contact
     */
    private function findOrCreateContact(string $senderId, string $channel): Contact
    {
        $contact = Contact::where('sender_id', $senderId)->first();
        
        if (!$contact) {
            Log::info('Creating new contact', ['sender_id' => $senderId, 'channel' => $channel]);
            
            $contact = Contact::create([
                'sender_id' => $senderId,
                'name' => "User {$senderId}", // Will be updated in Phase 5
                'channel' => $channel,
                'tags' => ['new']
            ]);
        }

        return $contact;
    }

    /**
     * Find or create conversation for contact
     * 
     * @param Contact $contact
     * @return Conversation
     */
    private function findOrCreateConversation(Contact $contact): Conversation
    {
        // Look for active conversation
        $conversation = Conversation::where('contact_id', $contact->_id)
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            Log::info('Creating new conversation', ['contact_id' => $contact->_id]);
            
            $conversation = Conversation::create([
                'contact_id' => $contact->_id,
                'title' => "Conversation with {$contact->name}",
                'status' => 'active'
            ]);
        }

        return $conversation;
    }

    /**
     * Create message in conversation
     * 
     * @param Conversation $conversation
     * @param array $messageInfo
     * @return Message
     */
    private function createMessage(Conversation $conversation, array $messageInfo): Message
    {
        return Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => $messageInfo['sender_id'],
            'text' => $messageInfo['text'],
            'message_type' => $messageInfo['message_type'],
            'raw_payload' => $messageInfo['raw_payload']
        ]);
    }
}
