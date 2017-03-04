<?php
use Buttress\Collection\GeneratorCollection as C;
require __DIR__ . '/../vendor/autoload.php';

$i = 1;
$title = function($string) use (&$i) {
    out('', $i++ . '. ' . $string);
};

$title('Create collection:');
out(C::make('string'));

$title('Convert to array:');
$array = C::make([1, 2])->all();
echo print_r($array, 1);

$title('Convert to JSON:');
echo C::make([1, 2])->toJson() . PHP_EOL;
echo json_encode(C::make([1, 2])) . PHP_EOL;

$title('IteratorAggregate:');
$iterator = C::make([1, 2])->getIterator();
out('Is this an iterator: ' . ($iterator instanceof Iterator ? 'Yes' : 'No'));
out(iterator_to_array($iterator));

$title('Zip together with another collection');
out('> Items in position 0 are positive odd numbers, items in position 1 are positive multiples of 5');
$infiniteCollection1 = C::make(nToN(1, INF, 2)); // All odd numbers greater than 0
$infiniteCollection2 = C::make(nToN(5, INF, 5)); // All multiples of 5 above 999
out($infiniteCollection1->zip($infiniteCollection2)->take(10)); // Output the first 10

$title('Flatten: Convert to single dimensional array');
// Same thing from #4
$infiniteCollection1 = C::make(nToN(1, INF, 2)); // All odd numbers greater than 0
$infiniteCollection2 = C::make(nToN(5, INF, 5)); // All multiples of 5 above 999

out($infiniteCollection1->zip($infiniteCollection2)->take(10)->flatten()); // Flatten and output the first 10

$title('Slice: Get items after an offset, optionally limited to a length');
out('Offset 10, length not provided', C::make(nToN(1, 20))->slice(10));
out('Offset 5, length 3', C::make(nToN(1, 20))->slice(5, 3));

$title('Values: Reset the keys');
out('Same slice from #6:', C::make(nToN(1, 20))->slice(5, 3)->values());

$title('Flip: Replace the keys with the values and vice versa');
out('Same slice from #6:', C::make(nToN(1, 20))->slice(5, 3)->flip());

$title('Math');
$numbers = iterator_to_array(randomNumbers(25));
out('Numbers to test with: ' . json_encode($numbers));
out('Average: ' . C::make($numbers)->avg());
out('Median: ' . C::make($numbers)->median());
out('Mode: ' . json_encode(C::make($numbers)->mode()));
out('Sum: ' . C::make($numbers)->sum());
out('Min: ' . C::make($numbers)->min());
out('Max: ' . C::make($numbers)->max());

$title('Map: Replace each value with the output of a callback');
$collection = C::make(randomNumbers(10, 65, 90))
    ->map(function($value) {
        return chr($value);
    });
out('Convert randomnumbers to characters:', $collection);

$title('MapKeys: Replace each value and key with the output of a callback');
$collection = C::make(randomNumbers(3, 65, 90))
    ->map(function($value, $key) {
        return ["Key: {$key}", "Value: {$value}"];
    });
out('Convert randomnumbers to characters:', $collection);

$title('Reduce: Collapse collection down into a single value');
$collection = C::make(randomNumbers(10, 65, 90))
    ->reduce(function(string $carry, int $number) {
        return $carry . chr($number);
    }, '');
out('Reduce random numbers into a string:', $collection);

$title('Pluck: Pull a value out of each item');
out(C::make([['name' => 'Tory', 'role' => 'Professor'], ['name' => 'Jamie', 'role' => 'Student']])->pluck('name'));
out(C::make([['name' => 'Tory', 'role' => 'Professor'], ['name' => 'Jamie', 'role' => 'Student']])->pluck('name', 'role'));

$title('First: Get the first item');
out(C::make(range(1, 100))->first());

$title('Last: Get the last item');
out('Warning: Make sure you\'re iterating over a finite set!');
out(C::make(range(1, 100))->last());

$title('Merge: Combine two values');
out(C::make([0,1,2])->merge(C::make([100,101,102]))->values());
out(C::make([0,1,2])->merge([100,101,102])->values());

$title('nth: Get every nth value');
$primes = C::make(primes());
out($primes->nth(100)->take(10));

$title('Only: Only get items with the specified keys');
$primes = C::make(primes());
out($primes->only([
    2, 4, 6
])->take(3));

$title('Only: Only get items with the specified keys');
$primes = C::make(primes());
out($primes->only([
    2, 4, 6
])->take(3));

$title('forPage: Pagination');
out('All primes, 10 per page, page 50');
out(C::make(primes())->forPage(50, 10));

$title('Partition: Separate the collection into two collections');
out(C::make(randomNumbers(4))->partition(function($value) {
    return $value < 50;
}));

$title('Pipe: Passes the collection into a passed callback and returns the result');
out(C::make(randomNumbers(4))->pipe(function(C $collection) {
    return json_encode('Sum: ' . $collection->sum(), true);
}));

$title('Prepend: Add a value to the beginning of the collection');
out(C::make(randomNumbers(2))->prepend('prepended'));

$title('Push: Add a value to the beginning of the collection');
out(C::make(randomNumbers(2))->push('appended'));

$title('Put: Replace a value with a key ');
out(C::make(randomNumbers(3))->put(1, 'replaced by key'));

$title('Reject: remove value');
out(C::make(range(1, 3))->reject(2)->values());

$title('Chunk: Output list in equal sized chunks');
out(C::make(range(1, 6))->chunk(3)->map(function(C $collection) {
    return $collection->values();
}));

$title('Splice');
out(C::make(range(1, 5))->splice(1));
out(C::make(range(1, 5))->splice(2, -1));
out(C::make(range(1, 5))->splice(2, 1, ['a', 'b']));

$title('Take: Take n items from the beginning or from the end of the collection');
out('Take 2', C::make(range(1,3))->take(2));
out('Take -2', C::make(range(1,3))->take(-2)->values());

$title('Unique: Only output unique items');
out('Note: If you are looping over a large data set, you might want to filter instead. This method requires extra state.');
out(C::make(randomNumbers(5, 0, 5))->unique()->values());
