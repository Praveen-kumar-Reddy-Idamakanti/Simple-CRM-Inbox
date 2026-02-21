<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * Message Model - Represents individual messages in conversations
 * 
 * @property string $_id MongoDB ObjectId
 * @property string $conversation_id Foreign key to Conversation model
 * @property string $sender_type Type of sender (contact, agent, system)
 * @property string $sender_id ID of the sender (contact_id or agent_id)
 * @property string $text Message content/text
 * @property string $message_type Type of message (text, image, file, etc.)
 * @property array $raw_payload Original webhook payload from messaging platform
 * @property array $attachments Array of file/image attachments
 * @property bool $is_read Whether the message has been read by agents
 * @property \Carbon\Carbon $created_at When the message was created
 * @property \Carbon\Carbon $updated_at When the message was last updated
 * @property string $platform_message_id Original message ID from platform
 * @property array $metadata Additional message metadata
 * @property bool $is_deleted Whether the message is deleted (soft delete)
 */
class Message extends Model
{
    use SoftDeletes;
    
    protected $connection = 'mongodb';
    
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'text',
        'message_type',
        'raw_payload',
        'attachments',
        'is_read',
        'platform_message_id',
        'metadata',
        'is_deleted'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_deleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    // Default values
    protected $attributes = [
        'is_read' => false,
        'is_deleted' => false,
        'metadata' => null
    ];

    /**
     * Relationship: Get the conversation this message belongs to
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Scope: Get messages by sender type
     */
    public function scopeBySenderType($query, $senderType)
    {
        return $query->where('sender_type', $senderType);
    }

    /**
     * Scope: Get messages from contacts only
     */
    public function scopeFromContact($query)
    {
        return $query->where('sender_type', 'contact');
    }

    /**
     * Scope: Get messages from agents only
     */
    public function scopeFromAgent($query)
    {
        return $query->where('sender_type', 'agent');
    }

    /**
     * Scope: Get unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Get read messages
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: Get messages by message type
     */
    public function scopeMessageType($query, $messageType)
    {
        return $query->where('message_type', $messageType);
    }

    /**
     * Scope: Get non-deleted messages
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
    }

    /**
     * Soft delete message
     */
    public function softDelete()
    {
        $this->is_deleted = true;
        $this->save();
    }

    /**
     * Add attachment to message
     */
    public function addAttachment($attachment)
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = $attachment;
        $this->attachments = $attachments;
        $this->save();
    }

    /**
     * Check if message is from contact
     */
    public function isFromContact()
    {
        return $this->sender_type === 'contact';
    }

    /**
     * Check if message is from agent
     */
    public function isFromAgent()
    {
        return $this->sender_type === 'agent';
    }

    /**
     * Get formatted message text
     */
    public function getFormattedTextAttribute()
    {
        $text = $this->text;
        
        // Handle different message types
        switch ($this->message_type) {
            case 'image':
                return "[Image] " . ($text ? $text : "Image shared");
            case 'file':
                return "[File] " . ($text ? $text : "File shared");
            case 'system':
                return "[System] " . $text;
            default:
                return $text;
        }
    }
}
