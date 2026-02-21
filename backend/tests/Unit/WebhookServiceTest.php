<?php

namespace Tests\Unit;

use App\Services\WebhookService;
use App\Services\ProfileService;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    protected $webhookService;

    protected function setUp(): void
    {
        parent::setUp();
        $profileService = new ProfileService();
        $this->webhookService = new WebhookService($profileService);
    }

    /** @test */
    public function it_can_process_valid_instagram_webhook()
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'test_user_123'],
                    'message' => ['text' => 'Hello from Instagram']
                ]]
            ]]
        ];

        $this->webhookService->processWebhook($payload);

        $this->assertDatabaseHas('contacts', [
            'sender_id' => 'test_user_123'
        ]);

        $this->assertDatabaseHas('messages', [
            'text' => 'Hello from Instagram'
        ]);
    }

    /** @test */
    public function it_can_process_valid_facebook_webhook()
    {
        $payload = [
            'object' => 'page',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'fb_test_user_123'],
                    'message' => ['text' => 'Hello from Facebook']
                ]]
            ]]
        ];

        $this->webhookService->processWebhook($payload);

        $this->assertDatabaseHas('contacts', [
            'sender_id' => 'fb_test_user_123'
        ]);

        $this->assertDatabaseHas('messages', [
            'text' => 'Hello from Facebook'
        ]);
    }

    /** @test */
    public function it_can_extract_message_info_from_instagram_payload()
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'extract_test_123'],
                    'message' => ['text' => 'Test message']
                ]]
            ]]
        ];

        $reflection = new \ReflectionClass($this->webhookService);
        $method = $reflection->getMethod('extractMessageInfo');
        $method->setAccessible(true);

        $messageInfo = $method->invoke($this->webhookService, $payload);

        $this->assertEquals('extract_test_123', $messageInfo['sender_id']);
        $this->assertEquals('instagram', $messageInfo['channel']);
        $this->assertEquals('Test message', $messageInfo['text']);
        $this->assertEquals('text', $messageInfo['message_type']);
    }

    /** @test */
    public function it_can_detect_message_type_text()
    {
        $message = ['text' => 'Simple text message'];

        $reflection = new \ReflectionClass($this->webhookService);
        $method = $reflection->getMethod('detectMessageType');
        $method->setAccessible(true);

        $messageType = $method->invoke($this->webhookService, $message);

        $this->assertEquals('text', $messageType);
    }

    /** @test */
    public function it_can_detect_message_type_image()
    {
        $message = [
            'text' => 'Look at this',
            'attachments' => [[
                'type' => 'image',
                'url' => 'https://example.com/image.jpg'
            ]]
        ];

        $reflection = new \ReflectionClass($this->webhookService);
        $method = $reflection->getMethod('detectMessageType');
        $method->setAccessible(true);

        $messageType = $method->invoke($this->webhookService, $message);

        $this->assertEquals('image', $messageType);
    }

    /** @test */
    public function it_can_detect_message_type_file()
    {
        $message = [
            'text' => 'File attached',
            'attachments' => [[
                'type' => 'file',
                'url' => 'https://example.com/file.pdf'
            ]]
        ];

        $reflection = new \ReflectionClass($this->webhookService);
        $method = $reflection->getMethod('detectMessageType');
        $method->setAccessible(true);

        $messageType = $method->invoke($this->webhookService, $message);

        $this->assertEquals('file', $messageType);
    }

    /** @test */
    public function it_creates_contact_conversation_and_message_for_new_contact()
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'new_user_123'],
                    'message' => ['text' => 'Hello new']
                ]]
            ]]
        ];

        $this->webhookService->processWebhook($payload);

        $contact = Contact::where('sender_id', 'new_user_123')->first();
        $this->assertNotNull($contact);

        $conversation = Conversation::where('contact_id', $contact->_id)->first();
        $this->assertNotNull($conversation);

        $messages = Message::where('conversation_id', $conversation->_id)->get();
        $this->assertEquals(1, $messages->count());
    }

    /** @test */
    public function it_does_not_duplicate_contact_for_existing_user()
    {
        $contact = Contact::create([
            'sender_id' => 'existing_user_123',
            'name' => 'Existing User',
            'channel' => 'instagram'
        ]);

        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'existing_user_123'],
                    'message' => ['text' => 'Another message']
                ]]
            ]]
        ];

        $this->webhookService->processWebhook($payload);

        $this->assertEquals(
            1,
            Contact::where('sender_id', 'existing_user_123')->count()
        );

        $conversation = Conversation::where('contact_id', $contact->_id)->first();
        $this->assertNotNull($conversation);

        $messages = Message::where('conversation_id', $conversation->_id)->get();
        $this->assertEquals(1, $messages->count());
    }

    /** @test */
    public function it_handles_multiple_messages_from_same_contact()
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [
                    [
                        'sender' => ['id' => 'multi_user_123'],
                        'message' => ['text' => 'First']
                    ],
                    [
                        'sender' => ['id' => 'multi_user_123'],
                        'message' => ['text' => 'Second']
                    ]
                ]
            ]]
        ];

        $this->webhookService->processWebhook($payload);

        $contact = Contact::where('sender_id', 'multi_user_123')->first();
        $this->assertNotNull($contact);

        $conversation = Conversation::where('contact_id', $contact->_id)->first();
        $this->assertNotNull($conversation);

        $messages = Message::where('conversation_id', $conversation->_id)->get();
        $this->assertEquals(2, $messages->count());
    }

    /** @test */
    public function it_handles_empty_payload_gracefully()
    {
        $this->webhookService->processWebhook([]);

        $this->assertEquals(0, Contact::count());
        $this->assertEquals(0, Conversation::count());
        $this->assertEquals(0, Message::count());
    }
}