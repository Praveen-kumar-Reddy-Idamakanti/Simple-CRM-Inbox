<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Running Comprehensive Unit Tests\n\n";

// Use Laravel's testing framework
use Tests\Unit\ContactModelTest;
use Tests\Unit\ConversationModelTest;
use Tests\Unit\MessageModelTest;
use Tests\Unit\WebhookServiceTest;
use Tests\Unit\ProfileServiceTest;
use Tests\Unit\ContactControllerTest;

class UnitTestRunner
{
    private $results = [];
    private $startTime;
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $errors = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function runAllTests()
    {
        echo "🚀 Starting Unit Test Execution...\n\n";
        
        // Run all test classes
        $this->runTestSuite('Contact Model Tests', ContactModelTest::class);
        $this->runTestSuite('Conversation Model Tests', ConversationModelTest::class);
        $this->runTestSuite('Message Model Tests', MessageModelTest::class);
        $this->runTestSuite('Webhook Service Tests', WebhookServiceTest::class);
        $this->runTestSuite('Profile Service Tests', ProfileServiceTest::class);
        $this->runTestSuite('Contact Controller Tests', ContactControllerTest::class);
        
        $this->printSummary();
    }

    private function runTestSuite($suiteName, $testClass)
    {
        echo "📋 Testing {$suiteName}\n";
        echo "==================\n";

        try {
            // Create test instance with a dummy test name
            $testInstance = new $testClass('dummy_test');
            $methods = get_class_methods($testInstance);
            
            foreach ($methods as $method) {
                if (is_string($method) && strpos($method, 'test_') === 0) {
                    $this->totalTests++;
                    
                    try {
                        $testInstance->setUp();
                        $testInstance->$method();
                        $this->passedTests++;
                        echo "  ✅ {$method}\n";
                    } catch (\Exception $e) {
                        $this->failedTests++;
                        $this->errors[] = [
                            'test' => $suiteName . '::' . $method,
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ];
                        echo "  ❌ {$method} - {$e->getMessage()}\n";
                    }
                }
            }
        } catch (\Exception $e) {
            echo "  ❌ Failed to run {$suiteName}: {$e->getMessage()}\n";
            $this->errors[] = [
                'test' => $suiteName,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
        
        echo "\n";
    }

    private function printSummary()
    {
        $endTime = microtime(true);
        $duration = round($endTime - $this->startTime, 2);
        
        echo "📊 Unit Test Results Summary\n";
        echo "===================\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests} ✅\n";
        echo "Failed: {$this->failedTests} ❌\n";
        
        if ($this->totalTests > 0) {
            echo "Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 1) . "%\n";
        } else {
            echo "Success Rate: N/A (No tests found)\n";
        }
        
        echo "Duration: {$duration}s\n\n";

        if (!empty($this->errors)) {
            echo "❌ Failed Tests:\n";
            foreach ($this->errors as $error) {
                echo "  • {$error['test']} - {$error['error']}\n";
                echo "    File: {$error['file']}:{$error['line']}\n";
            }
            echo "\n";
        }

        echo "🎯 Unit Testing Complete!\n";
        
        if ($this->passedTests === $this->totalTests && $this->totalTests > 0) {
            echo "🎉 All tests passed! Excellent code quality!\n";
        } elseif ($this->totalTests > 0 && $this->passedTests / $this->totalTests >= 0.8) {
            echo "✅ Great success! Most tests passed.\n";
        } elseif ($this->totalTests === 0) {
            echo "⚠️ No tests were found. Check test class setup.\n";
        } else {
            echo "⚠️ Some tests failed. Review the errors above.\n";
        }

        echo "\n📝 Test Coverage Areas:\n";
        echo "  • Models: Contact, Conversation, Message\n";
        echo "  • Services: WebhookService, ProfileService\n";
        echo "  • Controllers: ContactController\n";
        echo "  • Business Logic: CRUD operations, validation, relationships\n";
        echo "  • Error Handling: Exception management, HTTP status codes\n";
        echo "  • Data Integrity: Database operations, relationships\n";
        echo "  • Input Validation: Tag formatting, payload validation\n";
        echo "  • Service Integration: External API calls, caching\n";
    }
}

// Run the tests
$runner = new UnitTestRunner();
$runner->runAllTests();
