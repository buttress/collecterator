<?php
require_once __DIR__ . "/../vendor/autoload.php";
use Buttress\Collection\GeneratorCollection as C;

/**
 * Pass the contents of this file into this test:
 *
 *     $ php ReadFromInput.php < ReadFromInput.php
 *
 * 1 // You should see this
 * 51 // You should see this
 * 25 74 // You should see this
 * 2 // You should NOT see this
 * 52 // You should NOT see this
 * 26 74 // You should NOT see this
 *
 */
// Get the input
if (!is_resource(STDIN)) {
    out('You must pipe in some data');
    exit;
}

// Only output lines whose numbers add up to an odd number
C::make(streamFile(STDIN))->filter(function($input) {
    return stringSum($input) % 2;
})->each(function($input) {
    echo $input;
})->close();
