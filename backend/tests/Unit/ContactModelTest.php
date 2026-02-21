<?php

namespace Tests\Unit;

use App\Models\Contact;
use Tests\TestCase;

class ContactModelTest extends TestCase
{

    /** @test */
    public function it_can_create_a_contact()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_123',
            'name' => 'Test User',
            'channel' => 'instagram',
            'tags' => ['new', 'vip']
        ]);

        $this->assertNotNull($contact);
        $this->assertEquals('test_user_123', $contact->sender_id);
        $this->assertEquals('Test User', $contact->name);
        $this->assertEquals('instagram', $contact->channel);
        $this->assertEquals(['new', 'vip'], $contact->tags);
        $this->assertTrue($contact->is_active);
    }

    /** @test */
    public function it_can_find_contact_by_sender_id()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_456',
            'name' => 'Test User 2',
            'channel' => 'page'
        ]);

        $foundContact = Contact::where('sender_id', 'test_user_456')->first();
        
        $this->assertNotNull($foundContact);
        $this->assertEquals('Test User 2', $foundContact->name);
        $this->assertEquals('page', $foundContact->channel);
    }

    /** @test */
    public function it_can_update_last_seen_timestamp()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_789',
            'name' => 'Test User 3',
            'channel' => 'instagram'
        ]);

        $originalLastSeen = $contact->last_seen;
        
        $contact->updateLastSeen();
        
        $this->assertNotEquals($originalLastSeen, $contact->last_seen);
        $this->assertNotNull($contact->last_seen);
    }

    /** @test */
    public function it_can_add_tags_to_contact()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_101',
            'name' => 'Test User 4',
            'channel' => 'page',
            'tags' => ['new']
        ]);

        $contact->tags = array_merge($contact->tags, ['vip', 'priority']);
        $contact->save();

        $this->assertEquals(['new', 'vip', 'priority'], $contact->tags);
    }

    /** @test */
    public function it_can_remove_tags_from_contact()
    {
        $contact = Contact::create([
            'sender_id' => 'test_user_202',
            'name' => 'Test User 5',
            'channel' => 'instagram',
            'tags' => ['new', 'vip', 'priority']
        ]);

        $contact->tags = array_values(array_filter($contact->tags, fn($tag) => $tag !== 'vip'));
        $contact->save();

        $this->assertEquals(['new', 'priority'], $contact->tags);
    }

    /** @test */
    public function it_can_check_if_contact_is_active()
    {
        $activeContact = Contact::create([
            'sender_id' => 'active_user_1',
            'name' => 'Active User',
            'channel' => 'page',
            'is_active' => true
        ]);

        $inactiveContact = Contact::create([
            'sender_id' => 'inactive_user_1',
            'name' => 'Inactive User',
            'channel' => 'instagram',
            'is_active' => false
        ]);

        $this->assertTrue($activeContact->is_active);
        $this->assertFalse($inactiveContact->is_active);
    }

    /** @test */
    public function it_can_store_metadata()
    {
        $metadata = [
            'profile_source' => 'mock_server',
            'fetched_at' => now()->toISOString(),
            'raw_profile' => [
                'followers' => 1000,
                'following' => 500
            ]
        ];

        $contact = Contact::create([
            'sender_id' => 'metadata_user_1',
            'name' => 'Metadata User',
            'channel' => 'instagram',
            'metadata' => $metadata
        ]);

        $this->assertEquals($metadata, $contact->metadata);
        $this->assertEquals('mock_server', $contact->metadata['profile_source']);
        $this->assertEquals(1000, $contact->metadata['raw_profile']['followers']);
    }

    /** @test */
    public function it_can_scope_to_active_contacts()
    {
        // Create active and inactive contacts
        Contact::create([
            'sender_id' => 'active_scope_1',
            'name' => 'Active Scope 1',
            'channel' => 'page',
            'is_active' => true
        ]);

        Contact::create([
            'sender_id' => 'inactive_scope_1',
            'name' => 'Inactive Scope 1',
            'channel' => 'instagram',
            'is_active' => false
        ]);

        Contact::create([
            'sender_id' => 'active_scope_2',
            'name' => 'Active Scope 2',
            'channel' => 'page',
            'is_active' => true
        ]);

        $activeContacts = Contact::active()->get();
        
        $this->assertEquals(2, $activeContacts->count());
        $this->assertTrue($activeContacts->every('is_active', true));
    }

    /** @test */
    public function it_can_scope_to_channel()
    {
        Contact::create([
            'sender_id' => 'instagram_scope_1',
            'name' => 'Instagram Scope 1',
            'channel' => 'instagram'
        ]);

        Contact::create([
            'sender_id' => 'page_scope_1',
            'name' => 'Page Scope 1',
            'channel' => 'page'
        ]);

        Contact::create([
            'sender_id' => 'instagram_scope_2',
            'name' => 'Instagram Scope 2',
            'channel' => 'instagram'
        ]);

        $instagramContacts = Contact::channel('instagram')->get();
        $pageContacts = Contact::channel('page')->get();
        
        $this->assertEquals(2, $instagramContacts->count());
        $this->assertEquals(1, $pageContacts->count());
        $this->assertTrue($instagramContacts->every('channel', 'instagram'));
        $this->assertTrue($pageContacts->every('channel', 'page'));
    }

    /** @test */
    public function it_can_scope_to_tag()
    {
        Contact::create([
            'sender_id' => 'tag_scope_1',
            'name' => 'Tag Scope 1',
            'channel' => 'instagram',
            'tags' => ['vip', 'priority']
        ]);

        Contact::create([
            'sender_id' => 'tag_scope_2',
            'name' => 'Tag Scope 2',
            'channel' => 'page',
            'tags' => ['new']
        ]);

        Contact::create([
            'sender_id' => 'tag_scope_3',
            'name' => 'Tag Scope 3',
            'channel' => 'instagram',
            'tags' => ['vip']
        ]);

        $vipContacts = Contact::tag('vip')->get();
        $newContacts = Contact::tag('new')->get();
        
        $this->assertEquals(2, $vipContacts->count());
        $this->assertEquals(1, $newContacts->count());
        $this->assertTrue($vipContacts->every(fn($c) => in_array('vip', $c->tags)));
        $this->assertTrue($newContacts->every(fn($c) => in_array('new', $c->tags)));
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $contact = Contact::create([
            'sender_id' => 'cast_test_1',
            'name' => 'Cast Test User',
            'channel' => 'instagram',
            'is_active' => true,
            'tags' => ['test'],
            'metadata' => ['test' => 'value']
        ]);

        $this->assertIsBool($contact->is_active);
        $this->assertIsArray($contact->tags);
        $this->assertIsArray($contact->metadata);
        $this->assertInstanceOf(\Carbon\Carbon::class, $contact->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $contact->updated_at);
    }

    /** @test */
    public function it_can_handle_empty_tags()
    {
        $contact = Contact::create([
            'sender_id' => 'empty_tags_1',
            'name' => 'Empty Tags User',
            'channel' => 'instagram',
            'tags' => []
        ]);

        $this->assertEquals([], $contact->tags);
        $this->assertIsArray($contact->tags);
    }

    /** @test */
    public function it_can_handle_null_metadata()
    {
        $contact = Contact::create([
            'sender_id' => 'null_metadata_1',
            'name' => 'Null Metadata User',
            'channel' => 'instagram'
        ]);

        $this->assertNull($contact->metadata);
    }

    /** @test */
    public function it_can_soft_delete_contact()
    {
        $contact = Contact::create([
            'sender_id' => 'soft_delete_1',
            'name' => 'Soft Delete User',
            'channel' => 'instagram'
        ]);

        $contactId = $contact->_id;
        
        $contact->delete();
        
        $deletedContact = Contact::find($contactId);
        $this->assertNull($deletedContact);
        
        $trashedContact = Contact::withTrashed()->find($contactId);
        $this->assertNotNull($trashedContact);
    }
}
