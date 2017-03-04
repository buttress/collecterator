<?php
use Buttress\Collection\GeneratorCollection as C;
require __DIR__ . '/../vendor/autoload.php';
/**
 * `GeneratorCollection`s won't run any code until you attempt to iterate! There are a few good ways to access data:
 *
 * ```
 * // Get as array. Important: Make sure your collection isn't an infinite set!
 * $array = $collection->all()
 *
 * // Loop over the collection
 * foreach ($collection as $key => $value) {
 *     echo "{$key}: {$value}";
 * }
 *
 * // Ignore output and just iterate over the whole collection
 * $collection->close();
 * ```
 *
 * Remember that collections can only be iterated once, so only close your collection when you are ready
 */

/**
 * Example 1: Simple list filter
 */
out('# Example 1: Simple list filter');
$collection = C::make(range(0, 10));

// Apply a filter that removes even numbers
$odd = $collection
    ->filter(function(int $value) { return $value % 2; })
    ->values();

// We haven't actually done any work yet, so convert to array and output
out('Odd numbers between 0 and 10', $odd->all());


/**
 * Example 2: `->each()` doesn't do anything until you begin iterating
 */
out('', '# Example 2: `->each()` doesn\'t do anything until you begin iterating');
$collection = C::make(range(1, 10));

// Add an each wrapper
$collection = $collection->each(function($value) {
    // If the value is 5, we've made it half way so we should output
    if ($value === 5) {
        out(' > We\'ve iterated 5 times!');
    }

    // If we ever get to the last item in this list, lets throw an exception
    if ($value === 10) {
        throw new \RuntimeException('OH NO! This example looks broken, this shouldn\'t have run!');
    }
});

// If this weren't an `Iterator` / `Generator` based collection, the above `->each` would have already executed
// and an exception would've been thrown. Because we are using an iterator, we haven't run any code yet.

// Take only the first 5 items from this collection
$collection = $collection->take(5);

out('Beginning to iterate over collection');
$collection->close();
out('Everything worked, we didn\'t hit an exception!');

// Since the collection had only contained the first 5 items of the original list, the `->each()` callable only fired
// for those items.
