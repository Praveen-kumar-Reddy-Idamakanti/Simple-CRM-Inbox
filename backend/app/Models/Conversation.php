<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Conversation Model - Represents a conversation thread with a contact
 * 
 * @property string $_id MongoDB ObjectId
 * @property string $contact_id Foreign key to Contact model
 * @property string $title Optional conversation title/subject
 * @property string $status Conversation status (active, archived, closed, etc.)
 * @property \Carbon\Carbon $last_message_at Timestamp of the last message
 * @property \Carbon\Carbon $created_at When the conversation was created
 * @property \Carbon\Carbon $updated_at When the conversation was last updated
 * @property int $message_count Total number of messages in this conversation
 * @property string $last_message_preview Preview text of the last message
 * @property string $assigned_to Agent ID this conversation is assigned to
 * @property array $metadata Additional conversation metadata
 * @property bool $is_archived Whether the conversation is archived
 * @property int $unread_count Number of unread messages for agents
 */
class Conversation extends Model
{
    protected $connection = 'mongodb';
    
    protected $fillable = [
        'contact_id',
        'title',
        'status',
        'last_message_at',
        'message_count',
        'last_message_preview',
        'assigned_to',
        'metadata',
        'is_archived',
        'unread_count'
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_message_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'last_message_at'
    ];

    // Default values
    protected $attributes = [
        'status' => 'active',
        'message_count' => 0,
        'metadata' => [],
        'is_archived' => false,
        'unread_count' => 0
    ];

    /**
     * Relationship: Get the contact for this conversation
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * Relationship: Get all messages in this conversation
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * Scope: Get active conversations only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_archived', false);
    }

    /**
     * Scope: Get archived conversations
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Get conversations for a specific agent
     */
    public function scopeAssignedTo($query, $agentId)
    {
        return $query->where('assigned_to', $agentId);
    }

    /**
     * Scope: Get conversations with unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }

    /**
     * Update conversation with new message details
     */
    public function updateWithNewMessage($messageText, $senderType = 'contact')
    {
        $this->last_message_at = now();
        $this->message_count = ($this->message_count ?? 0) + 1;
        $this->last_message_preview = substr($messageText, 0, 100);
        
        // Increment unread count for contact messages
        if ($senderType === 'contact') {
            $this->unread_count = ($this->unread_count ?? 0) + 1;
        }
        
        $this->save();
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead()
    {
        $this->unread_count = 0;
        $this->save();
    }

    /**
     * Archive conversation
     */
    public function archive()
    {
        $this->is_archived = true;
        $this->status = 'archived';
        $this->save();
    }

    /**
     * Unarchive conversation
     */
    public function unarchive()
    {
        $this->is_archived = false;
        $this->status = 'active';
        $this->save();
    }
}
