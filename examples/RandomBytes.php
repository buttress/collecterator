<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Buttress\Collection\GeneratorCollection as C;

// Open urandom dev stream
$resource = fopen('/dev/urandom', 'r');
$stream = streamFile($resource, 128);

// Make a collection and call each. This works sort of like a "Promise" in that it
// doesn't do any iteration at this point, it just returns a new collection that will
// run this function on each iteration
$randomBytes = C::make($stream)->each(function($value) {
    // Output base64 encoded data + a newline
    echo base64_encode($value) . PHP_EOL;
});

// Since these are generators, no data has been read at this point. Let's ask for input to continue:
$lines = readline('We\'re ready to output keys, how many do you want? [inf|100]: ');

// If we only need a limited amount, lets take them. This essentially limits our collection to $lines
if (is_numeric($lines) && $lines > 0) {
    $randomBytes = $randomBytes->take($lines);
}

/**
 * Now we are ready to iterate over our collection!
 * Because we don't need to capture any output, we can just call ->close()
 */
$randomBytes->close();
fclose($resource);
