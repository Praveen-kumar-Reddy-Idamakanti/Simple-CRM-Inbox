<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Message;
use Tests\TestCase;

class ConversationModelTest extends TestCase
{

    /** @test */
    public function it_can_create_a_conversation()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_123',
            'name' => 'Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User',
            'status' => 'active',
            'message_count' => 0,
            'unread_count' => 0
        ]);

        $this->assertNotNull($conversation);
        $this->assertEquals($contact->_id, $conversation->contact_id);
        $this->assertEquals('Conversation with Test User', $conversation->title);
        $this->assertEquals('active', $conversation->status);
        $this->assertEquals(0, $conversation->message_count);
        $this->assertEquals(0, $conversation->unread_count);
        $this->assertFalse($conversation->is_archived);
    }

    /** @test */
    public function it_can_find_conversation_by_contact_id()
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

        $foundConversation = Conversation::where('contact_id', $contact->_id)->first();
        
        $this->assertNotNull($foundConversation);
        $this->assertEquals('Conversation with Test User 2', $foundConversation->title);
    }

    /** @test */
    public function it_can_update_with_new_message()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_789',
            'name' => 'Test User 3',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 3',
            'status' => 'active',
            'message_count' => 1,
            'unread_count' => 1
        ]);

        $originalMessageCount = $conversation->message_count;
        $originalUnreadCount = $conversation->unread_count;
        $originalLastMessageAt = $conversation->last_message_at;

        $conversation->updateWithNewMessage('Test message', 'contact');

        $this->assertEquals($originalMessageCount + 1, $conversation->message_count);
        $this->assertEquals($originalUnreadCount + 1, $conversation->unread_count);
        $this->assertEquals('Test message', $conversation->last_message_preview);
        $this->assertNotEquals($originalLastMessageAt, $conversation->last_message_at);
    }

    /** @test */
    public function it_can_mark_as_read()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_101',
            'name' => 'Test User 4',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 4',
            'status' => 'active',
            'unread_count' => 5
        ]);

        $conversation->markAsRead();

        $this->assertEquals(0, $conversation->unread_count);
    }

    /** @test */
    public function it_can_archive_conversation()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_202',
            'name' => 'Test User 5',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 5',
            'status' => 'active',
            'is_archived' => false
        ]);

        $conversation->archive();

        $this->assertTrue($conversation->is_archived);
        $this->assertEquals('archived', $conversation->status);
    }

    /** @test */
    public function it_can_unarchive_conversation()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_303',
            'name' => 'Test User 6',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 6',
            'status' => 'archived',
            'is_archived' => true
        ]);

        $conversation->unarchive();

        $this->assertFalse($conversation->is_archived);
        $this->assertEquals('active', $conversation->status);
    }

    /** @test */
    public function it_can_get_messages_relationship()
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

        // Create messages
        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'contact',
            'sender_id' => 'test_user_404',
            'text' => 'Hello from contact',
            'message_type' => 'text'
        ]);

        Message::create([
            'conversation_id' => $conversation->_id,
            'sender_type' => 'agent',
            'sender_id' => 'agent_system',
            'text' => 'Hello from agent',
            'message_type' => 'text'
        ]);

        $messages = $conversation->messages;
        
        $this->assertEquals(2, $messages->count());
        $this->assertEquals('Hello from contact', $messages[0]->text);
        $this->assertEquals('Hello from agent', $messages[1]->text);
    }

    /** @test */
    public function it_can_get_contact_relationship()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_505',
            'name' => 'Test User 8',
            'channel' => 'page',
            'tags' => ['vip', 'priority']
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Conversation with Test User 8',
            'status' => 'active'
        ]);

        $relatedContact = $conversation->contact;
        
        $this->assertNotNull($relatedContact);
        $this->assertEquals('Test User 8', $relatedContact->name);
        $this->assertEquals('page', $relatedContact->channel);
        $this->assertEquals(['vip', 'priority'], $relatedContact->tags);
    }

    /** @test */
    public function it_can_scope_to_active_conversations()
    {
        $contact = Contact::create([
            'sender_id' => 'scope_test_1',
            'name' => 'Scope Test User',
            'channel' => 'instagram'
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Active Conversation',
            'status' => 'active'
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Archived Conversation',
            'status' => 'archived',
            'is_archived' => true
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Another Active Conversation',
            'status' => 'active'
        ]);

        $activeConversations = Conversation::active()->get();
        
        $this->assertEquals(2, $activeConversations->count());
        $this->assertTrue($activeConversations->every('status', 'active'));
        $this->assertTrue($activeConversations->every('is_archived', false));
    }

    /** @test */
    public function it_can_scope_to_unread_conversations()
    {
        $contact = Contact::create([
            'sender_id' => 'unread_test_1',
            'name' => 'Unread Test User',
            'channel' => 'page'
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Unread Conversation',
            'status' => 'active',
            'unread_count' => 5
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Read Conversation',
            'status' => 'active',
            'unread_count' => 0
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Another Unread Conversation',
            'status' => 'active',
            'unread_count' => 2
        ]);

        $unreadConversations = Conversation::unread()->get();
        
        $this->assertEquals(2, $unreadConversations->count());
        $this->assertTrue($unreadConversations->every('unread_count', '>', 0));
    }

    /** @test */
    public function it_can_scope_to_archived_conversations()
    {
        $contact = Contact::create([
            'sender_id' => 'archived_test_1',
            'name' => 'Archived Test User',
            'channel' => 'instagram'
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Archived Conversation',
            'status' => 'archived',
            'is_archived' => true
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Active Conversation',
            'status' => 'active',
            'is_archived' => false
        ]);

        $archivedConversations = Conversation::archived()->get();
        
        $this->assertEquals(1, $archivedConversations->count());
        $this->assertTrue($archivedConversations->every('is_archived', true));
        $this->assertTrue($archivedConversations->every('status', 'archived'));
    }

    /** @test */
    public function it_can_scope_by_status()
    {
        $contact = Contact::create([
            'sender_id' => 'status_test_1',
            'name' => 'Status Test User',
            'channel' => 'page'
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Active Conversation',
            'status' => 'active'
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Archived Conversation',
            'status' => 'archived'
        ]);

        Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Closed Conversation',
            'status' => 'closed'
        ]);

        $activeConversations = Conversation::status('active')->get();
        $archivedConversations = Conversation::status('archived')->get();
        $closedConversations = Conversation::status('closed')->get();
        
        $this->assertEquals(1, $activeConversations->count());
        $this->assertEquals(1, $archivedConversations->count());
        $this->assertEquals(1, $closedConversations->count());
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
            'status' => 'active',
            'message_count' => 5,
            'unread_count' => 2,
            'is_archived' => false
        ]);

        $this->assertIsInt($conversation->message_count);
        $this->assertIsInt($conversation->unread_count);
        $this->assertIsBool($conversation->is_archived);
        $this->assertInstanceOf(\Carbon\Carbon::class, $conversation->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $conversation->updated_at);
        // last_message_at can be null for new conversations
        $this->assertTrue(
            $conversation->last_message_at === null || 
            $conversation->last_message_at instanceof \Carbon\Carbon
        );
    }

    /** @test */
    public function it_can_handle_zero_message_count()
    {
        $contact = Contact::create([
            'sender_id' => 'zero_test_1',
            'name' => 'Zero Test User',
            'channel' => 'instagram'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Zero Message Conversation',
            'status' => 'active',
            'message_count' => 0,
            'unread_count' => 0
        ]);

        $this->assertEquals(0, $conversation->message_count);
        $this->assertEquals(0, $conversation->unread_count);
    }

    /** @test */
    public function it_can_soft_delete_conversation()
    {
        $contact = Contact::create([
            'sender_id' => 'soft_delete_conv_1',
            'name' => 'Soft Delete Conv User',
            'channel' => 'page'
        ]);

        $conversation = Conversation::create([
            'contact_id' => $contact->_id,
            'title' => 'Soft Delete Conversation',
            'status' => 'active'
        ]);

        $conversationId = $conversation->_id;
        
        $conversation->delete();
        
        $deletedConversation = Conversation::find($conversationId);
        $this->assertNull($deletedConversation);
        
        $trashedConversation = Conversation::withTrashed()->find($conversationId);
        $this->assertNotNull($trashedConversation);
    }
}
