<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Add a tag to a contact
     * 
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function addTag(string $id, Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'tag' => 'required|string|max:50|regex:/^[a-zA-Z0-9\s\-_]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'Invalid tag format',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contact = Contact::where('sender_id', $id)->first();
            
            if (!$contact) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Contact not found'
                ], 404);
            }

            $tag = $request->input('tag');
            $normalizedTag = $this->normalizeTag($tag);

            // Add tag if not already present
            if (!in_array($normalizedTag, $contact->tags ?? [])) {
                $currentTags = $contact->tags ?? [];
                $currentTags[] = $normalizedTag;
                $contact->tags = $currentTags;
                $contact->save();
            }

            Log::info('Tag added to contact', [
                'contact_id' => $id,
                'tag' => $normalizedTag
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tag added successfully',
                'data' => [
                    'contact_id' => $contact->_id,
                    'tag' => $normalizedTag,
                    'tags' => $contact->tags ?? []
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Add tag error', [
                'contact_id' => $id,
                'tag' => $request->input('tag'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to add tag'
            ], 500);
        }
    }

    /**
     * Remove a tag from a contact
     * 
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function removeTag(string $id, Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'tag' => 'required|string|max:50|regex:/^[a-zA-Z0-9\s\-_]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'Invalid tag format',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contact = Contact::where('sender_id', $id)->first();
            
            if (!$contact) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Contact not found'
                ], 404);
            }

            $tag = $request->input('tag');
            $normalizedTag = $this->normalizeTag($tag);

            // Remove tag if present
            $currentTags = $contact->tags ?? [];
            if (in_array($normalizedTag, $currentTags)) {
                $currentTags = array_filter($currentTags, function ($t) use ($normalizedTag) {
                    return $t !== $normalizedTag;
                });
                $contact->tags = array_values($currentTags); // Re-index array
                $contact->save();
            }

            Log::info('Tag removed from contact', [
                'contact_id' => $id,
                'tag' => $normalizedTag
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tag removed successfully',
                'data' => [
                    'contact_id' => $contact->_id,
                    'tag' => $normalizedTag,
                    'tags' => $contact->tags ?? []
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Remove tag error', [
                'contact_id' => $id,
                'tag' => $request->input('tag'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to remove tag'
            ], 500);
        }
    }

    /**
     * Get contact details with tags
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $contact = Contact::where('sender_id', $id)->first();
            
            if (!$contact) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Contact not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $contact->_id,
                    'sender_id' => $contact->sender_id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'channel' => $contact->channel,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'tags' => $contact->tags ?? [],
                    'is_active' => $contact->is_active,
                    'last_seen' => $contact->last_seen,
                    'created_at' => $contact->created_at,
                    'updated_at' => $contact->updated_at,
                    'metadata' => $contact->metadata ?? []
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get contact error', [
                'contact_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to get contact'
            ], 500);
        }
    }

    /**
     * Normalize tag string
     * 
     * @param string $tag
     * @return string
     */
    private function normalizeTag(string $tag): string
    {
        // Trim whitespace
        $tag = trim($tag);
        
        // Convert to lowercase for consistency
        $tag = strtolower($tag);
        
        // Replace multiple spaces with single space
        $tag = preg_replace('/\s+/', ' ', $tag);
        
        // Remove leading/trailing spaces again
        $tag = trim($tag);
        
        return $tag;
    }

    /**
     * Validate tag format
     * 
     * @param string $tag
     * @return bool
     */
    private function isValidTag(string $tag): bool
    {
        // Check if tag contains only allowed characters
        return preg_match('/^[a-zA-Z0-9\s\-_]+$/', $tag) && 
               strlen($tag) > 0 && 
               strlen($tag) <= 50;
    }
}
