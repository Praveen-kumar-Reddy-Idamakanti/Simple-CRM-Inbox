<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProfileService
{
    protected $apiKey;
    protected $baseUrl;
    protected $cacheTtl;

    public function __construct()
    {
        $this->apiKey = config('services.mock.api_key', env('API_KEY')); // Use config first, fallback to env
        $this->baseUrl = 'https://mock-simulation.omts.in';
        $this->cacheTtl = 3600; // Cache for 1 hour
    }

    /**
     * Fetch and enrich contact profile from mock server
     * 
     * @param string $senderId
     * @param string $channel
     * @return array|null Profile data or null if failed
     */
    public function enrichContact(string $senderId, string $channel): ?array
    {
        // Check cache first to avoid repeated API calls
        $cacheKey = "profile_{$senderId}";
        if (Cache::has($cacheKey)) {
            Log::info("Profile cache hit for sender: {$senderId}");
            return Cache::get($cacheKey);
        }

        try {
            Log::info("Fetching profile for sender: {$senderId} from channel: {$channel}");

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->get("{$this->baseUrl}/profile/{$senderId}");

            if (!$response->successful()) {
                Log::warning("Profile fetch failed", [
                    'sender_id' => $senderId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            $profileData = $response->json();
            
            // Validate and normalize profile data
            if ($profileData === null) {
                $profileData = [];
            }
            $normalizedProfile = $this->normalizeProfileData($profileData, $channel);
            
            // Cache the result
            Cache::put($cacheKey, $normalizedProfile, $this->cacheTtl);
            
            Log::info("Profile fetched successfully", [
                'sender_id' => $senderId,
                'name' => $normalizedProfile['name'] ?? 'Unknown'
            ]);

            return $normalizedProfile;

        } catch (\Exception $e) {
            Log::error("Profile fetch error", [
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Normalize profile data from mock server response
     * 
     * @param array $profileData
     * @param string $channel
     * @return array
     */
    private function normalizeProfileData(array $profileData, string $channel): array
    {
        return [
            'name' => $profileData['name'] ?? $this->generateDefaultName($profileData, $channel),
            'avatar' => $profileData['avatar'] ?? $profileData['profile_pic'] ?? $profileData['picture_url'] ?? null,
            'channel' => $channel,
            'email' => $profileData['email'] ?? null,
            'phone' => $profileData['phone'] ?? null,
            'metadata' => [
                'profile_source' => 'mock_server',
                'fetched_at' => now()->toISOString(),
                'raw_profile' => $profileData
            ]
        ];
    }

    /**
     * Generate default name if not provided in profile
     * 
     * @param array $profileData
     * @param string $channel
     * @return string
     */
    private function generateDefaultName(array $profileData, string $channel): string
    {
        // Try various field names that might contain name
        $possibleNameFields = ['username', 'handle', 'display_name', 'full_name', 'first_name'];
        
        foreach ($possibleNameFields as $field) {
            if (!empty($profileData[$field])) {
                return $profileData[$field];
            }
        }

        // Fallback to channel-specific naming
        return match ($channel) {
            'instagram' => "Instagram User",
            'page' => "Facebook User",
            'whatsapp' => "WhatsApp User",
            default => ucfirst($channel) . " User"
        };
    }

    /**
     * Check if profile fetching is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Clear profile cache for a specific sender
     * 
     * @param string $senderId
     * @return void
     */
    public function clearProfileCache(string $senderId): void
    {
        Cache::forget("profile_{$senderId}");
    }

    /**
     * Clear all profile cache
     * 
     * @return void
     */
    public function clearAllProfileCache(): void
    {
        // This would require cache tags or a more sophisticated approach
        // For now, we'll implement a simple cache clearing method
        Log::info("Clearing all profile cache (implementation needed for production)");
    }

    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'cache_ttl' => $this->cacheTtl,
            'api_key_set' => !empty($this->apiKey),
            'base_url' => $this->baseUrl
        ];
    }

    /**
     * Normalize tag string
     * 
     * @param string $tag
     * @return string
     */
    private function normalizeTag(string $tag): string
    {
        return strtolower(trim($tag));
    }
}
