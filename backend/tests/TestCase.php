<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear MongoDB collections before each test
        $this->clearMongoDBCollections();
    }
    
    /**
     * Clear all MongoDB collections
     *
     * @return void
     */
    protected function clearMongoDBCollections(): void
    {
        try {
            // Get MongoDB connection
            $connection = DB::connection('mongodb');
            
            // List and drop collections
            $collections = ['contacts', 'conversations', 'messages'];
            foreach ($collections as $collection) {
                try {
                    $connection->dropCollection($collection);
                } catch (\Exception $e) {
                    // Collection might not exist, that's okay
                }
            }
        } catch (\Exception $e) {
            // If MongoDB isn't available, tests will fail gracefully
            \Illuminate\Support\Facades\Log::warning('MongoDB not available for testing: ' . $e->getMessage());
        }
    }
}
