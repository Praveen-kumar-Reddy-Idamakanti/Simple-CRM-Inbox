<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;

class MongoDBIndexSeeder extends Seeder
{
    /**
     * Run the database seeds to create MongoDB indexes.
     */
    public function run(): void
    {
        $this->command->info('Creating MongoDB indexes...');

        // Contacts collection indexes
        $this->createContactsIndexes();

        // Conversations collection indexes
        $this->createConversationsIndexes();

        // Messages collection indexes
        $this->createMessagesIndexes();

        $this->command->info('MongoDB indexes created successfully!');
    }

    /**
     * Create indexes for contacts collection
     */
    private function createContactsIndexes(): void
    {
        $this->command->info('Creating contacts collection indexes...');

        // Unique index on sender_id to prevent duplicates
        DB::connection('mongodb')
            ->getCollection('contacts')
            ->createIndex(['sender_id' => 1], ['unique' => true]);

        // Index for searching by channel
        DB::connection('mongodb')
            ->getCollection('contacts')
            ->createIndex(['channel' => 1]);

        // Index for tags (multikey index)
        DB::connection('mongodb')
            ->getCollection('contacts')
            ->createIndex(['tags' => 1]);

        // Compound index for active contacts by channel
        DB::connection('mongodb')
            ->getCollection('contacts')
            ->createIndex(['is_active' => 1, 'channel' => 1]);

        // Index for last_seen sorting
        DB::connection('mongodb')
            ->getCollection('contacts')
            ->createIndex(['last_seen' => -1]);

        // Text index for name search
        DB::connection('mongodb')
            ->getCollection('contacts')
            ->createIndex(['name' => 'text']);

        $this->command->info('✅ Contacts indexes created');
    }

    /**
     * Create indexes for conversations collection
     */
    private function createConversationsIndexes(): void
    {
        $this->command->info('Creating conversations collection indexes...');

        // Index for contact_id to find conversations by contact
        DB::connection('mongodb')
            ->getCollection('conversations')
            ->createIndex(['contact_id' => 1]);

        // Index for last_message_at for sorting conversations
        DB::connection('mongodb')
            ->getCollection('conversations')
            ->createIndex(['last_message_at' => -1]);

        // Compound index for active conversations sorted by last message
        DB::connection('mongodb')
            ->getCollection('conversations')
            ->createIndex([
                'status' => 1,
                'is_archived' => 1,
                'last_message_at' => -1
            ]);

        // Index for assigned_to
        DB::connection('mongodb')
            ->getCollection('conversations')
            ->createIndex(['assigned_to' => 1]);

        // Index for unread_count
        DB::connection('mongodb')
            ->getCollection('conversations')
            ->createIndex(['unread_count' => 1]);

        $this->command->info('✅ Conversations indexes created');
    }

    /**
     * Create indexes for messages collection
     */
    private function createMessagesIndexes(): void
    {
        $this->command->info('Creating messages collection indexes...');

        // Index for conversation_id to find messages by conversation
        DB::connection('mongodb')
            ->getCollection('messages')
            ->createIndex(['conversation_id' => 1]);

        // Compound index for conversation messages sorted by creation time
        DB::connection('mongodb')
            ->getCollection('messages')
            ->createIndex([
                'conversation_id' => 1,
                'created_at' => 1
            ]);

        // Index for sender_type
        DB::connection('mongodb')
            ->getCollection('messages')
            ->createIndex(['sender_type' => 1]);

        // Index for is_read
        DB::connection('mongodb')
            ->getCollection('messages')
            ->createIndex(['is_read' => 1]);

        // Compound index for unread messages by conversation
        DB::connection('mongodb')
            ->getCollection('messages')
            ->createIndex([
                'conversation_id' => 1,
                'is_read' => 1,
                'sender_type' => 1
            ]);

        // Index for is_deleted (soft deletes)
        DB::connection('mongodb')
            ->getCollection('messages')
            ->createIndex(['is_deleted' => 1]);

        // Text index for message content search
        DB::connection('mongodb')
            ->getCollection('messages')
            ->createIndex(['text' => 'text']);

        // TTL index for automatic cleanup of old messages (optional - 2 years)
        // DB::connection('mongodb')
        //     ->getCollection('messages')
        //     ->createIndex(['created_at' => 1], ['expireAfterSeconds' => 63072000]);

        $this->command->info('✅ Messages indexes created');
    }
}
