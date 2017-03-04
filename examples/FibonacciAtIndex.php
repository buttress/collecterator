<?php
use Buttress\Collection\GeneratorCollection as C;

require __DIR__ . '/../vendor/autoload.php';

// Get the input
$index = $_REQUEST['index'] ?? $argv[1] ?? null;

// Output some helpful info
if ($index === null) {
    die('Either set the ?index=1 get variable or provide the index as the first argument' . PHP_EOL);
}

// Output the Fibonacci item at the requested index
echo C::make(fibonacci())->slice($index)->first() . PHP_EOL;
