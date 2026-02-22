<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;

$messages = Message::where('sender_type', 'contact')
    ->whereNotNull('text')
    ->where('text', '!=', '')
    ->pluck('text')
    ->toArray();

$unique = array_values(array_unique($messages));
$subset = array_slice($unique, 0, 500);

file_put_contents('unique_messages_500.json', json_encode($subset, JSON_PRETTY_PRINT));
echo "Saved " . count($subset) . " unique messages.\n";
