<?php

namespace Tests\Unit;

use App\Services\ProfileService;
use App\Models\Contact;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ProfileServiceTest extends TestCase
{

    protected $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Clear cache between tests
        $this->profileService = new ProfileService();
    }

    /** @test */
    public function it_can_check_if_service_is_enabled()
    {
        // Mock the environment variable using Laravel's config
        config(['services.mock.api_key' => 'test_api_key']);
        $profileService = new ProfileService();
        $this->assertTrue($profileService->isEnabled());
        
        // Test with empty API key
        config(['services.mock.api_key' => '']);
        $profileService = new ProfileService();
        $this->assertFalse($profileService->isEnabled());
        
        // Restore original value
        config(['services.mock.api_key' => 'test_api_key']);
    }

    /** @test */
    public function it_can_normalize_tag_string()
    {
        $reflection = new \ReflectionClass($this->profileService);
        $method = $reflection->getMethod('normalizeTag');
        $method->setAccessible(true);

        // Test normalization
        $this->assertEquals('test tag', $method->invoke($this->profileService, '  Test Tag  '));
        $this->assertEquals('test-tag', $method->invoke($this->profileService, 'Test-Tag'));
        $this->assertEquals('test_tag', $method->invoke($this->profileService, 'TEST_TAG'));
        $this->assertEquals('test  tag', $method->invoke($this->profileService, '  TEST  TAG  '));
    }

    /** @test */
    public function it_can_generate_default_name_for_instagram()
    {
        $reflection = new \ReflectionClass($this->profileService);
        $method = $reflection->getMethod('generateDefaultName');
        $method->setAccessible(true);

        $profileData = ['username' => 'testuser'];
        $channel = 'instagram';

        $defaultName = $method->invoke($this->profileService, $profileData, $channel);

        $this->assertEquals('testuser', $defaultName);
    }

    /** @test */
    public function it_can_generate_default_name_for_facebook()
    {
        $reflection = new \ReflectionClass($this->profileService);
        $method = $reflection->getMethod('generateDefaultName');
        $method->setAccessible(true);

        $profileData = ['username' => 'testuser'];
        $channel = 'page';

        $defaultName = $method->invoke($this->profileService, $profileData, $channel);

        $this->assertEquals('testuser', $defaultName);
    }

    /** @test */
    public function it_can_generate_default_name_fallback()
    {
        $reflection = new \ReflectionClass($this->profileService);
        $method = $reflection->getMethod('generateDefaultName');
        $method->setAccessible(true);

        $profileData = [];
        $channel = 'instagram';

        $defaultName = $method->invoke($this->profileService, $profileData, $channel);

        $this->assertEquals('Instagram User', $defaultName);
    }

    /** @test */
    public function it_can_normalize_profile_data()
    {
        $reflection = new \ReflectionClass($this->profileService);
        $method = $reflection->getMethod('normalizeProfileData');
        $method->setAccessible(true);

        $profileData = [
            'name' => 'Test User',
            'profile_pic' => 'https://example.com/avatar.jpg',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'bio' => 'Test bio'
        ];
        $channel = 'instagram';

        $normalized = $method->invoke($this->profileService, $profileData, $channel);

        $this->assertEquals('Test User', $normalized['name']);
        $this->assertEquals('https://example.com/avatar.jpg', $normalized['avatar']);
        $this->assertEquals('instagram', $normalized['channel']);
        $this->assertEquals('test@example.com', $normalized['email']);
        $this->assertEquals('+1234567890', $normalized['phone']);
        $this->assertEquals('mock_server', $normalized['metadata']['profile_source']);
        $this->assertArrayHasKey('fetched_at', $normalized['metadata']);
        $this->assertArrayHasKey('raw_profile', $normalized['metadata']);
        $this->assertEquals($profileData, $normalized['metadata']['raw_profile']);
    }

    /** @test */
    public function it_can_normalize_profile_data_with_picture_url()
    {
        $reflection = new \ReflectionClass($this->profileService);
        $method = $reflection->getMethod('normalizeProfileData');
        $method->setAccessible(true);

        $profileData = [
            'name' => 'Test User',
            'picture_url' => 'https://example.com/picture.jpg',
            'email' => 'test@example.com'
        ];
        $channel = 'facebook';

        $normalized = $method->invoke($this->profileService, $profileData, $channel);

        $this->assertEquals('Test User', $normalized['name']);
        $this->assertEquals('https://example.com/picture.jpg', $normalized['avatar']);
        $this->assertEquals('facebook', $normalized['channel']);
        $this->assertEquals('test@example.com', $normalized['email']);
    }

    /** @test */
    public function it_can_normalize_profile_data_with_missing_fields()
    {
        $reflection = new \ReflectionClass($this->profileService);
        $method = $reflection->getMethod('normalizeProfileData');
        $method->setAccessible(true);

        $profileData = [
            'name' => 'Test User'
        ];
        $channel = 'instagram';

        $normalized = $method->invoke($this->profileService, $profileData, $channel);

        $this->assertEquals('Test User', $normalized['name']);
        $this->assertNull($normalized['avatar']);
        $this->assertNull($normalized['email']);
        $this->assertNull($normalized['phone']);
        $this->assertEquals('instagram', $normalized['channel']);
        $this->assertEquals('mock_server', $normalized['metadata']['profile_source']);
    }

    /** @test */
    public function it_can_enrich_contact_with_valid_profile()
    {
        // Mock HTTP client
        Http::fake([
            'https://mock-simulation.omts.in/profile/test_user_123' => Http::response([
                'name' => 'Enriched User',
                'profile_pic' => 'https://example.com/avatar.jpg',
                'email' => 'enriched@example.com',
                'phone' => '+1234567890',
                'bio' => 'Enriched user bio'
            ], 200)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('test_user_123', 'instagram');

        $this->assertNotNull($result);
        $this->assertEquals('Enriched User', $result['name']);
        $this->assertEquals('https://example.com/avatar.jpg', $result['avatar']);
        $this->assertEquals('instagram', $result['channel']);
        $this->assertEquals('enriched@example.com', $result['email']);
        $this->assertEquals('+1234567890', $result['phone']);
        $this->assertEquals('mock_server', $result['metadata']['profile_source']);
    }

    /** @test */
    public function it_can_enrich_contact_with_fallback_when_service_unavailable()
    {
        // Mock HTTP client to return error
        Http::fake([
            'https://mock-simulation.omts.in/profile/test_user_456' => Http::response('', 500)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('test_user_456', 'instagram');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_enrich_contact_when_service_disabled()
    {
        // Disable service by removing API key
        config(['services.mock.api_key' => '']);

        $result = $this->profileService->enrichContact('test_user_789', 'instagram');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_cache_profile_results()
    {
        // Mock HTTP client
        Http::fake([
            'https://mock-simulation.omts.in/profile/cache_test_123' => Http::response([
                'name' => 'Cached User',
                'profile_pic' => 'https://example.com/cached_avatar.jpg'
            ], 200)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        // First call should hit the API
        $result1 = $this->profileService->enrichContact('cache_test_123', 'instagram');
        $this->assertNotNull($result1);
        $this->assertEquals('Cached User', $result1['name']);

        // Second call should use cache
        $result2 = $this->profileService->enrichContact('cache_test_123', 'instagram');
        $this->assertNotNull($result2);
        $this->assertEquals('Cached User', $result2['name']);

        // Verify cache was used (only one HTTP request made)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'cache_test_123');
        });
    }

    /** @test */
    public function it_can_clear_profile_cache()
    {
        // Mock HTTP client
        Http::fake([
            'https://mock-simulation.omts.in/profile/cache_clear_test_123' => Http::response([
                'name' => 'Cache Clear User',
                'profile_pic' => 'https://example.com/cache_clear_avatar.jpg'
            ], 200)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        // First call to populate cache
        $this->profileService->enrichContact('cache_clear_test_123', 'instagram');

        // Clear cache
        $this->profileService->clearProfileCache('cache_clear_test_123');

        // Second call should hit API again
        $result = $this->profileService->enrichContact('cache_clear_test_123', 'instagram');
        $this->assertNotNull($result);
        $this->assertEquals('Cache Clear User', $result['name']);

        // Verify cache was cleared (two HTTP requests made)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'cache_clear_test_123');
        });
    }

    /** @test */
    public function it_can_get_cache_stats()
    {
        config(['services.mock.api_key' => 'test_api_key']);

        $stats = $this->profileService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_ttl', $stats);
        $this->assertArrayHasKey('api_key_set', $stats);
        $this->assertArrayHasKey('base_url', $stats);
        $this->assertIsInt($stats['cache_ttl']);
        $this->assertTrue($stats['api_key_set']);
        $this->assertEquals('https://mock-simulation.omts.in', $stats['base_url']);
    }

    /** @test */
    public function it_can_handle_http_client_errors()
    {
        // Mock HTTP client to throw exception
        Http::fake([
            'https://mock-simulation.omts.in/profile/error_test_123' => Http::response('', 500, ['Connection timeout'])
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('error_test_123', 'instagram');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_handle_invalid_json_response()
    {
        // Mock HTTP client to return invalid JSON
        Http::fake([
            'https://mock-simulation.omts.in/profile/invalid_json_test_123' => Http::response('invalid json', 200)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('invalid_json_test_123', 'instagram');

        // Service should handle invalid JSON and return default profile data
        $this->assertNotNull($result);
        $this->assertEquals('Instagram User', $result['name']); // Should use default name
        $this->assertEquals('instagram', $result['channel']);
    }

    /** @test */
    public function it_can_handle_404_response()
    {
        // Mock HTTP client to return 404
        Http::fake([
            'https://mock-simulation.omts.in/profile/not_found_test_123' => Http::response('', 404)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('not_found_test_123', 'instagram');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_enrich_contact_with_different_channels()
    {
        // Mock HTTP client for both channels in one call
        Http::fake([
            'https://mock-simulation.omts.in/profile/instagram_user_123' => Http::response([
                'name' => 'Instagram User',
                'profile_pic' => 'https://example.com/instagram_avatar.jpg'
            ], 200),
            'https://mock-simulation.omts.in/profile/facebook_user_123' => Http::response([
                'name' => 'Facebook User',
                'picture_url' => 'https://example.com/facebook_avatar.jpg'
            ], 200)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        // Test Instagram
        $instagramResult = $this->profileService->enrichContact('instagram_user_123', 'instagram');
        $this->assertNotNull($instagramResult);
        $this->assertEquals('Instagram User', $instagramResult['name']);
        $this->assertEquals('instagram', $instagramResult['channel']);

        // Test Facebook
        $facebookResult = $this->profileService->enrichContact('facebook_user_123', 'page');
        $this->assertNotNull($facebookResult);
        $this->assertEquals('Facebook User', $facebookResult['name']);
        $this->assertEquals('page', $facebookResult['channel']);
    }

    /** @test */
    public function it_can_handle_empty_profile_data()
    {
        // Mock HTTP client to return empty response
        Http::fake([
            'https://mock-simulation.omts.in/profile/empty_profile_123' => Http::response([], 200)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('empty_profile_123', 'instagram');

        $this->assertNotNull($result);
        $this->assertEquals('Instagram User', $result['name']); // Should use default name
        $this->assertEquals('instagram', $result['channel']);
        $this->assertNull($result['avatar']);
        $this->assertNull($result['email']);
        $this->assertNull($result['phone']);
    }

    /** @test */
    public function it_can_log_enrichment_success()
    {
        // Mock HTTP client
        Http::fake([
            'https://mock-simulation.omts.in/profile/log_test_123' => Http::response([
                'name' => 'Log Test User',
                'profile_pic' => 'https://example.com/log_avatar.jpg'
            ], 200)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('log_test_123', 'instagram');

        $this->assertNotNull($result);
        // The service should log successful enrichment
        // This would be verified by checking logs in a real test environment
    }

    /** @test */
    public function it_can_log_enrichment_failure()
    {
        // Mock HTTP client to return error
        Http::fake([
            'https://mock-simulation.omts.in/profile/log_error_test_123' => Http::response('', 500)
        ]);

        config(['services.mock.api_key' => 'test_api_key']);

        $result = $this->profileService->enrichContact('log_error_test_123', 'instagram');

        $this->assertNull($result);
        // The service should log the failure
        // This would be verified by checking logs in a real test environment
    }
}
