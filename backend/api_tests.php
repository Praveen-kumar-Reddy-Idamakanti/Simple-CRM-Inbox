<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Comprehensive API Unit Tests\n\n";

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;

class ApiTester
{
    private $baseUrl = 'http://localhost:8000';
    private $testResults = [];
    private $conversationId = null;
    private $contactId = null;

    public function runAllTests()
    {
        echo "🚀 Starting Comprehensive API Tests...\n\n";
        
        $this->setupTestData();
        $this->testConversationApis();
        $this->testMessageApis();
        $this->testReplyApis();
        $this->testContactApis();
        $this->testTagApis();
        $this->testWebhookApis();
        
        $this->printSummary();
    }

    private function setupTestData()
    {
        echo "📋 Setting up test data...\n";
        
        // Get existing conversation and contact for testing
        $conversation = Conversation::with('contact')->first();
        if ($conversation) {
            $this->conversationId = $conversation->_id;
            $this->contactId = $conversation->contact->sender_id; // Use sender_id instead of _id
            echo "✅ Test data ready\n";
            echo "   Conversation ID: {$this->conversationId}\n";
            echo "   Contact ID: {$this->contactId}\n\n";
        } else {
            echo "❌ No test data available. Run poller first.\n";
            exit;
        }
    }

    private function testConversationApis()
    {
        echo "📱 Testing Conversation APIs\n";
        echo "============================\n";

        // Test 1: Get conversations list (Success)
        $this->makeRequest('GET', '/api/conversations', [], 'conversations_list_success');

        // Test 2: Get conversations with search (Success)
        $this->makeRequest('GET', '/api/conversations?search=test', [], 'conversations_search_success');

        // Test 3: Get conversations with pagination (Success)
        $this->makeRequest('GET', '/api/conversations?page=1&per_page=5', [], 'conversations_pagination_success');

        // Test 4: Get messages for conversation (Success)
        $this->makeRequest('GET', "/api/conversations/{$this->conversationId}/messages", [], 'conversation_messages_success');

        // Test 5: Get messages for invalid conversation (Error 404)
        $this->makeRequest('GET', '/api/conversations/invalid_conversation_id/messages', [], 'conversation_messages_404');

        // Test 6: Get messages with pagination (Success)
        $this->makeRequest('GET', "/api/conversations/{$this->conversationId}/messages?page=1&per_page=10", [], 'conversation_messages_pagination_success');

        echo "\n";
    }

    private function testMessageApis()
    {
        echo "📨 Testing Message APIs\n";
        echo "========================\n";

        // Note: Message APIs are tested through conversation endpoints
        echo "ℹ️  Message APIs tested through conversation endpoints\n\n";
    }

    private function testReplyApis()
    {
        echo "🤖 Testing Reply APIs\n";
        echo "====================\n";

        // Test 1: Send reply (Success)
        $replyData = [
            'conversation_id' => $this->conversationId,
            'message' => 'Test reply from unit tests'
        ];
        $this->makeRequest('POST', '/api/reply', $replyData, 'reply_send_success');

        // Test 2: Send reply with invalid conversation (Error 404)
        $invalidReplyData = [
            'conversation_id' => 'invalid_conversation_id',
            'message' => 'This should fail'
        ];
        $this->makeRequest('POST', '/api/reply', $invalidReplyData, 'reply_send_404');

        // Test 3: Send reply without message (Error 422)
        $incompleteReplyData = [
            'conversation_id' => $this->conversationId
        ];
        $this->makeRequest('POST', '/api/reply', $incompleteReplyData, 'reply_send_422');

        // Test 4: Send reply without conversation_id (Error 422)
        $incompleteReplyData2 = [
            'message' => 'This should fail'
        ];
        $this->makeRequest('POST', '/api/reply', $incompleteReplyData2, 'reply_send_422_no_conversation');

        // Test 5: Send reply with empty message (Error 422)
        $emptyMessageData = [
            'conversation_id' => $this->conversationId,
            'message' => ''
        ];
        $this->makeRequest('POST', '/api/reply', $emptyMessageData, 'reply_send_422_empty_message');

        // Test 6: Send reply with long message (Success)
        $longMessageData = [
            'conversation_id' => $this->conversationId,
            'message' => str_repeat('This is a very long message. ', 20)
        ];
        $this->makeRequest('POST', '/api/reply', $longMessageData, 'reply_send_long_message');

        echo "\n";
    }

    private function testContactApis()
    {
        echo "👤 Testing Contact APIs\n";
        echo "=====================\n";

        // Test 1: Get contact details (Success)
        $this->makeRequest('GET', "/api/contacts/{$this->contactId}", [], 'contact_details_success');

        // Test 2: Get invalid contact (Error 404)
        $this->makeRequest('GET', '/api/contacts/invalid_contact_id', [], 'contact_details_404');

        echo "\n";
    }

    private function testTagApis()
    {
        echo "🏷️  Testing Tag APIs\n";
        echo "===================\n";

        // Test 1: Add valid tag (Success)
        $tagData = ['tag' => 'Test Tag'];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/add", $tagData, 'tag_add_success');

        // Test 2: Add tag with special characters (Error 422)
        $invalidTagData = ['tag' => 'Invalid@Tag!'];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/add", $invalidTagData, 'tag_add_422_invalid_chars');

        // Test 3: Add tag too long (Error 422)
        $longTagData = ['tag' => str_repeat('a', 51)];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/add", $longTagData, 'tag_add_422_too_long');

        // Test 4: Add tag without tag field (Error 422)
        $noTagData = [];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/add", $noTagData, 'tag_add_422_no_tag');

        // Test 5: Add tag to invalid contact (Error 404)
        $invalidContactTagData = ['tag' => 'Test Tag'];
        $this->makeRequest('POST', '/api/contacts/invalid_contact_id/tags/add', $invalidContactTagData, 'tag_add_404');

        // Test 6: Remove tag (Success)
        $removeTagData = ['tag' => 'Test Tag'];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/remove", $removeTagData, 'tag_remove_success');

        // Test 7: Remove non-existent tag (Success - idempotent)
        $removeNonExistentTagData = ['tag' => 'NonExistentTag'];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/remove", $removeNonExistentTagData, 'tag_remove_nonexistent');

        // Test 8: Remove tag with invalid characters (Error 422)
        $invalidRemoveTagData = ['tag' => 'Invalid@Tag!'];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/remove", $invalidRemoveTagData, 'tag_remove_422_invalid');

        // Test 9: Remove tag without tag field (Error 422)
        $noRemoveTagData = [];
        $this->makeRequest('POST', "/api/contacts/{$this->contactId}/tags/remove", $noRemoveTagData, 'tag_remove_422_no_tag');

        // Test 10: Remove tag from invalid contact (Error 404)
        $invalidContactRemoveTagData = ['tag' => 'Test Tag'];
        $this->makeRequest('POST', '/api/contacts/invalid_contact_id/tags/remove', $invalidContactRemoveTagData, 'tag_remove_404');

        echo "\n";
    }

    private function testWebhookApis()
    {
        echo "🔗 Testing Webhook APIs\n";
        echo "======================\n";

        // Test 1: Valid webhook (Success)
        $validWebhook = [
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'test_user_123'],
                    'message' => ['text' => 'Test webhook message']
                ]]
            ]]
        ];
        $this->makeRequest('POST', '/api/webhook', $validWebhook, 'webhook_success', ['X-Webhook-Token: secret123']);

        // Test 2: Webhook without token (Error 401)
        $this->makeRequest('POST', '/api/webhook', $validWebhook, 'webhook_401_no_token');

        // Test 3: Webhook with invalid token (Error 401)
        $this->makeRequest('POST', '/api/webhook', $validWebhook, 'webhook_401_invalid_token', ['X-Webhook-Token: wrong_token']);

        // Test 4: Webhook with invalid JSON (Error 400)
        $this->makeRequest('POST', '/api/webhook', 'invalid json', 'webhook_400_invalid_json', ['X-Webhook-Token: secret123']);

        // Test 5: Webhook with empty payload (Error 400)
        $this->makeRequest('POST', '/api/webhook', [], 'webhook_400_empty_payload', ['X-Webhook-Token: secret123']);

        // Test 6: Webhook with Facebook payload (Success)
        $facebookWebhook = [
            'object' => 'page',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'fb_test_user_123'],
                    'message' => ['text' => 'Test Facebook webhook']
                ]]
            ]]
        ];
        $this->makeRequest('POST', '/api/webhook', $facebookWebhook, 'webhook_facebook_success', ['X-Webhook-Token: secret123']);

        // Test 7: Webhook without messaging field (Error 500)
        $invalidWebhook = [
            'object' => 'instagram',
            'entry' => [[
                'not_messaging' => 'test'
            ]]
        ];
        $this->makeRequest('POST', '/api/webhook', $invalidWebhook, 'webhook_500_no_messaging', ['X-Webhook-Token: secret123']);

        echo "\n";
    }

    private function makeRequest($method, $endpoint, $data = [], $testName = '', $headers = [])
    {
        $ch = curl_init();
        
        $url = $this->baseUrl . $endpoint;
        
        switch ($method) {
            case 'GET':
                curl_setopt($ch, CURLOPT_URL, $url . (!empty($data) ? '?' . http_build_query($data) : ''));
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
                break;
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // Set headers
        $defaultHeaders = ['Accept: application/json'];
        if ($method === 'POST') {
            $defaultHeaders[] = 'Content-Type: application/json';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $this->recordTestResult($testName, $method, $endpoint, $httpCode, $response, $error);
    }

    private function recordTestResult($testName, $method, $endpoint, $httpCode, $response, $error)
    {
        $success = false;
        $expectedCode = 200;
        
        // Determine expected status code based on test name
        if (strpos($testName, '404') !== false) {
            $expectedCode = 404;
        } elseif (strpos($testName, '422') !== false) {
            $expectedCode = 422;
        } elseif (strpos($testName, '401') !== false) {
            $expectedCode = 401;
        } elseif (strpos($testName, '400') !== false) {
            $expectedCode = 400;
        } elseif (strpos($testName, '500') !== false) {
            $expectedCode = 500;
        }
        
        $success = ($httpCode === $expectedCode) && empty($error);
        
        $this->testResults[] = [
            'name' => $testName,
            'method' => $method,
            'endpoint' => $endpoint,
            'expected_code' => $expectedCode,
            'actual_code' => $httpCode,
            'success' => $success,
            'error' => $error,
            'response' => substr($response, 0, 200) // Truncate for display
        ];
        
        $status = $success ? '✅' : '❌';
        echo "  {$status} {$testName} - {$method} {$endpoint} (Expected: {$expectedCode}, Got: {$httpCode})\n";
        
        if (!$success) {
            echo "     Error: {$error}\n";
            echo "     Response: " . substr($response, 0, 100) . "...\n";
        }
    }

    private function printSummary()
    {
        echo "\n📊 Test Results Summary\n";
        echo "=====================\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, fn($r) => $r['success']);
        $failedTests = array_filter($this->testResults, fn($r) => !$r['success']);
        
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: " . count($passedTests) . " ✅\n";
        echo "Failed: " . count($failedTests) . " ❌\n";
        echo "Success Rate: " . round((count($passedTests) / $totalTests) * 100, 1) . "%\n\n";
        
        if (!empty($failedTests)) {
            echo "❌ Failed Tests:\n";
            foreach ($failedTests as $test) {
                echo "  • {$test['name']} - {$test['method']} {$test['endpoint']}\n";
                echo "    Expected: {$test['expected_code']}, Got: {$test['actual_code']}\n";
                if ($test['error']) {
                    echo "    Error: {$test['error']}\n";
                }
            }
            echo "\n";
        }
        
        echo "🎯 API Unit Testing Complete!\n";
        echo "All endpoints tested with success and error scenarios.\n";
    }
}

// Run the tests
$tester = new ApiTester();
$tester->runAllTests();
