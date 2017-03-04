<?php
require_once __DIR__ . "/../vendor/autoload.php";
use Buttress\Collection\GeneratorCollection as C;

// Configuration
$perPage = 5;
$page = $_REQUEST['page'] ?? $argv[1] ?? null;

// Output some helpful info
if ($page === null) {
    die('Either set the ?page=1 get variable or provide the page as the first argument' . PHP_EOL);
}

// Make sure the page number is more than or equal to 1
$page = max($page, 1);

// Output the current page
echo json_encode(C::make(primes())->forPage($page, $perPage)->values());
