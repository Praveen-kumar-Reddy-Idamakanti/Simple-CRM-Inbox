<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiService;
use App\Services\MockServerService;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookService
{
    protected $profileService;
    protected $aiService;
    protected $mockServer;

    public function __construct(
        ProfileService $profileService, 
        AiService $aiService,
        MockServerService $mockServer
    ) {
        $this->profileService = $profileService;
        $this->aiService = $aiService;
        $this->mockServer = $mockServer;
    }
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
        $messageInfos = $this->extractMessageInfos($payload);
        
        if (empty($messageInfos)) {
            Log::warning('No messages found in webhook payload', ['payload' => $payload]);
            return;
        }

        foreach ($messageInfos as $messageInfo) {
            Log::info('Processing message', [
                'sender_id' => $messageInfo['sender_id'],
                'channel' => $messageInfo['channel'],
                'text' => substr($messageInfo['text'], 0, 100)
            ]);

            // Ensure contact and conversation exist
            $this->ensureContactAndConversation($payload, $messageInfo);
        }
    }

    /**
     * Extract multiple message information from webhook payload
     * 
     * @param array $payload
     * @return array
     */
    private function extractMessageInfos(array $payload): array
    {
        try {
            $channel = $payload['object'] ?? 'unknown';
            
            $entries = $payload['entry'] ?? [];
            if (empty($entries) || !is_array($entries)) {
                return [];
            }

            $messageInfos = [];
            
            foreach ($entries as $entry) {
                $messagingList = $entry['messaging'] ?? [];
                
                if (empty($messagingList) || !is_array($messagingList)) {
                    continue;
                }

                foreach ($messagingList as $messaging) {
                    $sender = $messaging['sender'] ?? [];
                    $message = $messaging['message'] ?? [];

                    $messageInfo = [
                        'channel' => $channel,
                        'sender_id' => $sender['id'] ?? null,
                        'text' => $message['text'] ?? '',
                        'message_type' => $this->detectMessageType($message),
                        'raw_payload' => $payload
                    ];

                    if ($messageInfo['sender_id'] && !empty($messageInfo['text'])) {
                        $messageInfos[] = $messageInfo;
                    }
                }
            }

            return $messageInfos;

        } catch (Exception $e) {
            Log::error('Failed to extract message infos', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return [];
        }
    }

    /**
     * Extract message information from webhook payload (legacy single message)
     * 
     * @param array $payload
     * @return array|null
     */
    private function extractMessageInfo(array $payload): ?array
    {
        $messageInfos = $this->extractMessageInfos($payload);
        return $messageInfos[0] ?? null;
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

        // SIMULATION: Auto-reply from AI
        if ($messageInfo['text']) {
            $this->handleAutoReply($conversation, $messageInfo['text']);
        }

        Log::info('Message processed successfully', [
            'contact_id' => $contact->_id,
            'conversation_id' => $conversation->_id,
            'sender_id' => $senderId
        ]);
    }

    /**
     * Handle automatic AI reply for incoming messages
     */
    private function handleAutoReply(Conversation $conversation, string $text): void
    {
        try {
            $suggestion = $this->aiService->getSuggestion($conversation, $text);
            
            if ($suggestion) {
                // Create the agent message in DB
                $reply = Message::create([
                    'conversation_id' => $conversation->_id,
                    'sender_type' => 'agent',
                    'sender_id' => 'revio_ai_bot',
                    'text' => $suggestion,
                    'message_type' => 'text',
                    'metadata' => ['is_auto_reply' => true]
                ]);

                // Update conversation
                $conversation->updateWithNewMessage($suggestion, 'agent');
                
                // SEND back to customer via Mock Server (Integration)
                $this->mockServer->send($conversation->contact, $suggestion);

                Log::info('AI Auto-reply sent and forwarded to mock server', ['conversation_id' => $conversation->_id]);
            }
        } catch (Exception $e) {
            Log::error('AI Auto-reply failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Find or create contact with profile enrichment
     * 
     * @param string $senderId
     * @param string $channel
     * @return Contact
     */
    private function findOrCreateContact(string $senderId, string $channel): Contact
    {
        $contact = Contact::where('sender_id', $senderId)->first();
        
        $isFallbackName = false;
        if ($contact) {
            // Check if name is missing or a fallback (e.g. "Instagram User")
            $name = $contact->name ?? '';
            $isFallbackName = (empty($name) || str_contains($name, 'User') || str_contains($name, 'fb_') || str_contains($name, 'ig_'));
        }

        if (!$contact || $isFallbackName) {
            // Try to enrich contact with profile data
            $profileData = null;
            if ($this->profileService->isEnabled()) {
                $profileData = $this->profileService->enrichContact($senderId, $channel);
            }
            
            if (!$contact) {
                // Determine a decent initial name without platform symbols
                $displayChannel = $channel === 'page' ? 'Facebook' : ucfirst($channel);
                
                $defaultName = $displayChannel . " User";
                if ($senderId) {
                    $shortId = substr($senderId, -6);
                    $defaultName = "{$displayChannel} ({$shortId})";
                }

                $contactData = [
                    'sender_id' => $senderId,
                    'name' => $defaultName,
                    'channel' => $channel,
                    'tags' => ['new'],
                    'metadata' => ['profile_source' => 'init_fallback']
                ];
                
                if ($profileData) {
                    $contactData = array_merge($contactData, $profileData);
                }
                
                $contact = Contact::create($contactData);
                Log::info('Created contact ' . ($profileData ? 'with' : 'without') . ' profile data', ['name' => $contact->name]);
            } elseif ($profileData) {
                // Update existing fallback contact with real data
                $contact->update($profileData);
                Log::info('Upgraded fallback contact with real profile', ['name' => $contact->name]);
            }
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
        $messageData = [
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => $messageInfo['sender_id'],
            'text' => $messageInfo['text'],
            'message_type' => $messageInfo['message_type'],
            'raw_payload' => $messageInfo['raw_payload']
        ];

        // Add attachments if present in the original message
        if (isset($messageInfo['raw_payload']['entry'][0]['messaging'][0]['message']['attachments'])) {
            $attachments = $messageInfo['raw_payload']['entry'][0]['messaging'][0]['message']['attachments'];
            $messageData['attachments'] = $attachments;
        }

        return Message::create($messageData);
    }
}
