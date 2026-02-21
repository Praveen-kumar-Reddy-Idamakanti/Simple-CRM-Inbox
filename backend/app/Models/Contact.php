<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * Contact Model - Represents a contact person in the CRM system
 * 
 * @property string $_id MongoDB ObjectId
 * @property string $sender_id Unique identifier from the messaging platform (IG, FB, etc.)
 * @property string $name Display name of the contact
 * @property string $channel Messaging channel (instagram, facebook, whatsapp, etc.)
 * @property string $avatar URL to profile picture/avatar
 * @property array $tags Array of tags for categorization (vip, customer, lead, etc.)
 * @property string $email Contact email address (optional)
 * @property string $phone Contact phone number (optional)
 * @property array $metadata Additional platform-specific data
 * @property \Carbon\Carbon $created_at When the contact was first created
 * @property \Carbon\Carbon $updated_at When the contact was last updated
 * @property bool $is_active Whether the contact is active
 * @property string $last_seen Timestamp of last interaction
 */
class Contact extends Model
{
    use SoftDeletes;
    
    protected $connection = 'mongodb';
    
    protected $fillable = [
        'sender_id',
        'name', 
        'channel',
        'avatar',
        'tags',
        'email',
        'phone',
        'metadata',
        'is_active',
        'last_seen'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_seen' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at', 
        'last_seen'
    ];

    // Default values
    protected $attributes = [
        'tags' => [],
        'metadata' => null,
        'is_active' => true
    ];

    /**
     * Relationship: Get all conversations for this contact
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'contact_id');
    }

    /**
     * Scope: Get active contacts only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get contacts by channel
     */
    public function scopeChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope: Get contacts with specific tag
     */
    public function scopeTag($query, $tag)
    {
        return $query->where('tags', $tag);
    }

    /**
     * Add a tag to the contact
     */
    public function addTag($tag)
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }
    }

    /**
     * Remove a tag from the contact
     */
    public function removeTag($tag)
    {
        $tags = $this->tags ?? [];
        $key = array_search($tag, $tags);
        if ($key !== false) {
            unset($tags[$key]);
            $this->tags = array_values($tags);
            $this->save();
        }
    }

    /**
     * Update last seen timestamp
     */
    public function updateLastSeen()
    {
        $this->last_seen = now();
        $this->save();
    }
}
