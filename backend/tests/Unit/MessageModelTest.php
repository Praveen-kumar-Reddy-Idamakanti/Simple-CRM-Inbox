<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\Contact;
use Tests\TestCase;

class MessageModelTest extends TestCase
{

    /** @test */
    public function it_can_create_a_message()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_123',
            'name' => 'Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_123',
            'text' => 'Hello from contact',
            'message_type' => 'text',
            'is_read' => false
        ]);

        $this->assertNotNull($message);
        $this->assertEquals($conversation->_id, $message->conversation_id);
        $this->assertEquals('contact', $message->sender_type);
        $this->assertEquals('test_user_123', $message->sender_id);
        $this->assertEquals('Hello from contact', $message->text);
        $this->assertEquals('text', $message->message_type);
        $this->assertFalse($message->is_read);
        $this->assertFalse($message->is_deleted);
    }

    /** @test */
    public function it_can_create_agent_message()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_456',
            'name' => 'Test User 2',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 2',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'agent',
            'sender_id' => 'agent_system',
            'text' => 'Hello from agent',
            'message_type' => 'text',
            'is_read' => true
        ]);

        $this->assertEquals('agent', $message->sender_type);
        $this->assertEquals('agent_system', $message->sender_id);
        $this->assertEquals('Hello from agent', $message->text);
        $this->assertTrue($message->is_read);
    }

    /** @test */
    public function it_can_find_message_by_conversation_id()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_789',
            'name' => 'Test User 3',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 3',
            'status' => 'active'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_789',
            'text' => 'Test message',
            'message_type' => 'text'
        ]);

        $foundMessage = Message::where('conversation_id', $conversation->_id)->first();
        
        $this->assertNotNull($foundMessage);
        $this->assertEquals('Test message', $foundMessage->text);
    }

    /** @test */
    public function it_can_mark_message_as_read()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_101',
            'name' => 'Test User 4',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 4',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_101',
            'text' => 'Unread message',
            'message_type' => 'text',
            'is_read' => false
        ]);

        $message->markAsRead();

        $this->assertTrue($message->is_read);
    }

    /** @test */
    public function it_can_soft_delete_message()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_202',
            'name' => 'Test User 5',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 5',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_202',
            'text' => 'Message to delete',
            'message_type' => 'text'
        ]);

        $message->delete();

        // With SoftDeletes trait, the model is soft deleted
        $this->assertTrue($message->trashed());
    }

    /** @test */
    public function it_can_get_conversation_relationship()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_303',
            'name' => 'Test User 6',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 6',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_303',
            'text' => 'Test relationship message',
            'message_type' => 'text'
        ]);

        $relatedConversation = $message->conversation;
        
        $this->assertNotNull($relatedConversation);
        $this->assertEquals('Conversation with Test User 6', $relatedConversation->title);
        $this->assertEquals('active', $relatedConversation->status);
    }

    /** @test */
    public function it_can_store_attachments()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_404',
            'name' => 'Test User 7',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 7',
            'status' => 'active'
        ]);

        $attachments = [
            [
                'type' => 'image',
                'url' => 'https://example.com/image.jpg',
                'filename' => 'image.jpg'
            ],
            [
                'type' => 'document',
                'url' => 'https://example.com/document.pdf',
                'filename' => 'document.pdf'
            ]
        ];

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_404',
            'text' => 'Message with attachments',
            'message_type' => 'image',
            'attachments' => $attachments
        ]);

        $this->assertEquals($attachments, $message->attachments);
        $this->assertEquals('image', $message->message_type);
    }

    /** @test */
    public function it_can_store_metadata()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_505',
            'name' => 'Test User 8',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 8',
            'status' => 'active'
        ]);

        $metadata = [
            'platform' => 'facebook',
            'device_type' => 'mobile',
            'client_version' => '1.0.0'
        ];

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_505',
            'text' => 'Message with metadata',
            'message_type' => 'text',
            'metadata' => $metadata
        ]);

        $this->assertEquals($metadata, $message->metadata);
        $this->assertEquals('facebook', $message->metadata['platform']);
    }

    /** @test */
    public function it_can_scope_to_contact_messages()
    {
        $contact = Contact::create([
            'sender_id' => 'scope_test_1',
            'name' => 'Scope Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Scope Test User',
            'status' => 'active'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'scope_test_1',
            'text' => 'Contact message',
            'message_type' => 'text'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'agent',
            'sender_id' => 'agent_system',
            'text' => 'Agent message',
            'message_type' => 'text'
        ]);

        $contactMessages = Message::fromContact()->get();
        
        $this->assertEquals(1, $contactMessages->count());
        $this->assertTrue($contactMessages->every('sender_type', 'contact'));
    }

    /** @test */
    public function it_can_scope_to_agent_messages()
    {
        $contact = Contact::create([
            'sender_id' => 'agent_scope_test_1',
            'name' => 'Agent Scope Test User',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Agent Scope Test User',
            'status' => 'active'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'agent_scope_test_1',
            'text' => 'Contact message',
            'message_type' => 'text'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'agent',
            'sender_id' => 'agent_system',
            'text' => 'Agent message',
            'message_type' => 'text'
        ]);

        $agentMessages = Message::fromAgent()->get();
        
        $this->assertEquals(1, $agentMessages->count());
        $this->assertTrue($agentMessages->every('sender_type', 'agent'));
    }

    /** @test */
    public function it_can_scope_to_unread_messages()
    {
        $contact = Contact::create([
            'sender_id' => 'unread_scope_test_1',
            'name' => 'Unread Scope Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Unread Scope Test User',
            'status' => 'active'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'unread_scope_test_1',
            'text' => 'Unread message',
            'message_type' => 'text',
            'is_read' => false
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'agent',
            'sender_id' => 'agent_system',
            'text' => 'Read message',
            'message_type' => 'text',
            'is_read' => true
        ]);

        $unreadMessages = Message::unread()->get();
        
        $this->assertEquals(1, $unreadMessages->count());
        $this->assertTrue($unreadMessages->every('is_read', false));
    }

    /** @test */
    public function it_can_scope_to_read_messages()
    {
        $contact = Contact::create([
            'sender_id' => 'read_scope_test_1',
            'name' => 'Read Scope Test User',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Read Scope Test User',
            'status' => 'active'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'read_scope_test_1',
            'text' => 'Unread message',
            'message_type' => 'text',
            'is_read' => false
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'agent',
            'sender_id' => 'agent_system',
            'text' => 'Read message',
            'message_type' => 'text',
            'is_read' => true
        ]);

        $readMessages = Message::read()->get();
        
        $this->assertEquals(1, $readMessages->count());
        $this->assertTrue($readMessages->every('is_read', true));
    }

    /** @test */
    public function it_can_scope_to_not_deleted_messages()
    {
        $contact = Contact::create([
            'sender_id' => 'not_deleted_test_1',
            'name' => 'Not Deleted Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Not Deleted Test User',
            'status' => 'active'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'not_deleted_test_1',
            'text' => 'Active message',
            'message_type' => 'text',
            'is_deleted' => false
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'not_deleted_test_1',
            'text' => 'Deleted message',
            'message_type' => 'text',
            'is_deleted' => true
        ]);

        $activeMessages = Message::notDeleted()->get();
        
        $this->assertEquals(1, $activeMessages->count());
        $this->assertTrue($activeMessages->every('is_deleted', false));
    }

    /** @test */
    public function it_can_scope_by_message_type()
    {
        $contact = Contact::create([
            'sender_id' => 'message_type_test_1',
            'name' => 'Message Type Test User',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Message Type Test User',
            'status' => 'active'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'message_type_test_1',
            'text' => 'Text message',
            'message_type' => 'text'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'message_type_test_1',
            'text' => 'Image message',
            'message_type' => 'image'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'message_type_test_1',
            'text' => 'File message',
            'message_type' => 'file'
        ]);

        $textMessages = Message::messageType('text')->get();
        $imageMessages = Message::messageType('image')->get();
        $fileMessages = Message::messageType('file')->get();
        
        $this->assertEquals(1, $textMessages->count());
        $this->assertEquals(1, $imageMessages->count());
        $this->assertEquals(1, $fileMessages->count());
        $this->assertTrue($textMessages->every('message_type', 'text'));
        $this->assertTrue($imageMessages->every('message_type', 'image'));
        $this->assertTrue($fileMessages->every('message_type', 'file'));
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $contact = Contact::create([
            'sender_id' => 'cast_test_1',
            'name' => 'Cast Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Cast Test Conversation',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'cast_test_1',
            'text' => 'Cast test message',
            'message_type' => 'text',
            'is_read' => false,
            'is_deleted' => false
        ]);

        $this->assertIsBool($message->is_read);
        $this->assertIsBool($message->is_deleted);
        $this->assertInstanceOf(\Carbon\Carbon::class, $message->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $message->updated_at);
    }

    /** @test */
    public function it_can_handle_empty_attachments()
    {
        $contact = Contact::create([
            'sender_id' => 'empty_attachments_test_1',
            'name' => 'Empty Attachments Test User',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Empty Attachments Test User',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'empty_attachments_test_1',
            'text' => 'Message without attachments',
            'message_type' => 'text',
            'attachments' => []
        ]);

        $this->assertEquals([], $message->attachments);
        $this->assertIsArray($message->attachments);
    }

    /** @test */
    public function it_can_handle_null_metadata()
    {
        $contact = Contact::create([
            'sender_id' => 'null_metadata_test_1',
            'name' => 'Null Metadata Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Null Metadata Test User',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'null_metadata_test_1',
            'text' => 'Message without metadata',
            'message_type' => 'text'
        ]);

        $this->assertNull($message->metadata);
    }

    /** @test */
    public function it_can_soft_delete_message_permanently()
    {
        $contact = Contact::create([
            'sender_id' => 'soft_delete_msg_2',
            'name' => 'Soft Delete Message User 2',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Soft Delete Message User 2',
            'status' => 'active'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'soft_delete_msg_2',
            'text' => 'Message to soft delete permanently',
            'message_type' => 'text'
        ]);

        $messageId = $message->_id;
        
        $message->delete();
        
        $deletedMessage = Message::find($messageId);
        $this->assertNull($deletedMessage);
        
        $trashedMessage = Message::withTrashed()->find($messageId);
        $this->assertNotNull($trashedMessage);
    }
}
