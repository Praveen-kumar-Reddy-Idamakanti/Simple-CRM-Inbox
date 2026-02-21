<?php

namespace Tests\Unit;

use App\Http\Controllers\ContactController;
use App\Models\Contact;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactControllerTest extends TestCase
{
    protected ContactController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ContactController();
    }

    /** @test */
    public function it_can_show_contact_details()
    {
        Contact::create([
            'sender_id' => 'controller_test_123',
            'name' => 'Controller Test User',
            'channel' => 'instagram',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'tags' => ['test', 'vip'],
            'is_active' => true,
            'metadata' => ['source' => 'test']
        ]);

        $response = $this->controller->show('controller_test_123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());

        $data = $response->getData(true);

        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Controller Test User', $data['data']['name']);
        $this->assertEquals(['test', 'vip'], $data['data']['tags']);
    }

    /** @test */
    public function it_returns_404_for_non_existent_contact()
    {
        $response = $this->controller->show('non_existent');

        $this->assertEquals(404, $response->status());

        $data = $response->getData(true);
        $this->assertEquals('Not Found', $data['error']);
    }

    /** @test */
    public function it_can_add_tag_to_contact()
    {
        $contact = Contact::create([
            'sender_id' => 'tag_test_123',
            'name' => 'Tag Test',
            'channel' => 'instagram',
            'tags' => ['new']
        ]);

        $request = Request::create('', 'POST', [
            'tag' => 'vip'
        ]);

        $response = $this->controller->addTag('tag_test_123', $request);

        $this->assertEquals(200, $response->status());

        $updated = Contact::find($contact->_id);
        $this->assertEquals(['new', 'vip'], $updated->tags);
    }

    /** @test */
    public function it_normalizes_tag_when_adding()
    {
        Contact::create([
            'sender_id' => 'normalize_test_123',
            'name' => 'Normalize Test',
            'channel' => 'instagram',
            'tags' => ['new']
        ]);

        $request = Request::create('', 'POST', [
            'tag' => '  VIP CUSTOMER  '
        ]);

        $response = $this->controller->addTag('normalize_test_123', $request);

        $this->assertEquals(200, $response->status());

        $data = $response->getData(true);
        $this->assertEquals('vip customer', $data['data']['tag']);
    }

    /** @test */
    public function it_prevents_duplicate_tags()
    {
        $contact = Contact::create([
            'sender_id' => 'duplicate_test_123',
            'name' => 'Duplicate Test',
            'channel' => 'instagram',
            'tags' => ['new']
        ]);

        $request = Request::create('', 'POST', ['tag' => 'vip']);
        $this->controller->addTag('duplicate_test_123', $request);
        $this->controller->addTag('duplicate_test_123', $request);

        $updated = Contact::find($contact->_id);
        $this->assertEquals(['new', 'vip'], $updated->tags);
    }

    /** @test */
    public function it_validates_invalid_tag_format()
    {
        Contact::create([
            'sender_id' => 'invalid_tag_test',
            'name' => 'Invalid Tag Test',
            'channel' => 'instagram',
            'tags' => []
        ]);

        $request = Request::create('', 'POST', [
            'tag' => 'Invalid@Tag!'
        ]);

        $response = $this->controller->addTag('invalid_tag_test', $request);

        $this->assertEquals(422, $response->status());
    }

    /** @test */
    public function it_can_remove_tag()
    {
        $contact = Contact::create([
            'sender_id' => 'remove_test_123',
            'name' => 'Remove Test',
            'channel' => 'instagram',
            'tags' => ['new', 'vip']
        ]);

        $request = Request::create('', 'POST', [
            'tag' => 'vip'
        ]);

        $response = $this->controller->removeTag('remove_test_123', $request);

        $this->assertEquals(200, $response->status());

        $updated = Contact::find($contact->_id);
        $this->assertEquals(['new'], $updated->tags);
    }

    /** @test */
    public function it_handles_removing_non_existent_tag_idempotently()
    {
        $contact = Contact::create([
            'sender_id' => 'remove_nonexistent',
            'name' => 'Remove Nonexistent',
            'channel' => 'instagram',
            'tags' => ['new']
        ]);

        $request = Request::create('', 'POST', [
            'tag' => 'ghost'
        ]);

        $response = $this->controller->removeTag('remove_nonexistent', $request);

        $this->assertEquals(200, $response->status());

        $updated = Contact::find($contact->_id);
        $this->assertEquals(['new'], $updated->tags);
    }

    /** @test */
    public function it_validates_tag_length()
    {
        Contact::create([
            'sender_id' => 'length_test',
            'name' => 'Length Test',
            'channel' => 'instagram',
            'tags' => []
        ]);

        $request = Request::create('', 'POST', [
            'tag' => str_repeat('a', 51)
        ]);

        $response = $this->controller->addTag('length_test', $request);

        $this->assertEquals(422, $response->status());
    }

    /** @test */
    public function it_returns_json_response()
    {
        Contact::create([
            'sender_id' => 'json_test',
            'name' => 'JSON Test',
            'channel' => 'instagram'
        ]);

        $response = $this->controller->show('json_test');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}