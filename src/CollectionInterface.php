<?php
namespace Buttress\Collection;

interface CollectionInterface
{

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get the average value of a given key.
     *
     * @param  callable|string|null $callback
     * @return float|null Null is returned if the collection is empty
     */
    public function avg($callback = null): ?float;

    /**
     * Alias for the "avg" method.
     *
     * @param  callable|string|null $callback
     * @return mixed Null is returned if the collection is empty
     */
    public function average($callback = null): ?float;

    /**
     * Get the median of a given key.
     *
     * @param  null $key
     * @return mixed Null is returned if the collection is empty
     */
    public function median($key = null): ?float;

    /**
     * Get the mode of a given key.
     *
     * @param  mixed $key
     * @return array|null
     */
    public function mode($key = null): ?array;

    /**
     * Collapse the collection of items into a single array.
     *
     * @return CollectionInterface
     */
    public function collapse(): CollectionInterface;

    /**
     * Execute a callback over each item.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function each(callable $callback): CollectionInterface;

    /**
     * Get all items except for those with the specified keys.
     *
     * @param  mixed $keys
     * @return CollectionInterface
     */
    public function except($keys): CollectionInterface;

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null $callback
     * @return CollectionInterface
     */
    public function filter(callable $callback = null): CollectionInterface;

    /**
     * Apply the callback if the value is truthy.
     *
     * @param  bool $value
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function when($value, callable $callback): CollectionInterface;

    /**
     * Filter items by key value pair
     * @param array $criteria
     * @return CollectionInterface
     */
    public function where(array $criteria): CollectionInterface;

    /**
     * Filter items by the given key value pair.
     *
     * @param  string $key
     * @param  mixed $values
     * @param  bool $strict
     * @return CollectionInterface
     */
    public function whereIn($key, $values, $strict = false): CollectionInterface;

    /**
     * Get the first item from the collection.
     *
     * @param  callable|null $callback
     * @param  mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null);

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int $depth
     * @return CollectionInterface
     */
    public function flatten($depth = INF): CollectionInterface;

    /**
     * Flip the items in the collection.
     * Swaps the keys with the values
     *
     * @return CollectionInterface
     */
    public function flip(): CollectionInterface;

    /**
     * Remove an item from the collection by key.
     *
     * @param  string|array $keys
     * @return CollectionInterface
     */
    public function forget($keys): CollectionInterface;

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  callable|string $groupBy
     * @param  bool $preserveKeys
     * @return CollectionInterface
     */
    public function groupBy($groupBy, $preserveKeys = false): CollectionInterface;

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  callable|string $keyBy
     * @return CollectionInterface
     */
    public function keyBy($keyBy): CollectionInterface;

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string $value
     * @param  string $glue
     * @return string
     */
    public function implode($value, $glue = null): string;

    /**
     * Get the keys of the collection items.
     *
     * @return CollectionInterface
     */
    public function keys(): CollectionInterface;

    /**
     * Get the last item from the collection.
     *
     * @param  callable|null $callback
     * @param  mixed $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null);

    /**
     * Get the values of a given key.
     *
     * @param string $value The key to pluck
     * @param string|null $key The key to use as name
     * @return CollectionInterface
     */
    public function pluck($value, $key = null): CollectionInterface;

    /**
     * Run a map over each of the items.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function map(callable $callback): CollectionInterface;

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function mapWithKeys(callable $callback): CollectionInterface;

    /**
     * Get the max value of a given key.
     *
     * @param  callable|string|null $callback
     * @return mixed
     */
    public function max($callback = null);

    /**
     * Get the min value of a given key.
     *
     * @param  callable|string|null $callback
     * @return mixed
     */
    public function min($callback = null);

    /**
     * Merge the collection with the given items.
     *
     * @param  mixed $items
     * @return CollectionInterface
     */
    public function merge($items): CollectionInterface;

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param  mixed $values
     * @return CollectionInterface
     */
    public function combine($values): CollectionInterface;

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int $step
     * @param  int $offset
     * @return CollectionInterface
     */
    public function nth($step, $offset = 0): CollectionInterface;

    /**
     * Get the items with the specified keys.
     *
     * @param  mixed $keys
     * @return CollectionInterface
     */
    public function only($keys): CollectionInterface;

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  int $page
     * @param  int $perPage
     * @return CollectionInterface
     */
    public function forPage(int $page, int $perPage): CollectionInterface;

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param  callable|string $callback
     * @return CollectionInterface[]
     */
    public function partition($callback): array;

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param  callable $callback
     * @return mixed
     */
    public function pipe(callable $callback);


    /**
     * Push an item onto the beginning of the collection.
     *
     * @param  mixed $value
     * @param  mixed $key
     * @return CollectionInterface
     */
    public function prepend($value, $key = null): CollectionInterface;

    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed $value
     * @return CollectionInterface
     */
    public function push($value): CollectionInterface;

    /**
     * Put an item in the collection by key.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return CollectionInterface
     */
    public function put($key, $value): CollectionInterface;

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable $callback
     * @param  mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param  callable|mixed $callback
     * @return CollectionInterface
     */
    public function reject($callback): CollectionInterface;

    /**
     * Slice the underlying collection array.
     *
     * @param  int $offset
     * @param  int $length
     * @return CollectionInterface
     */
    public function slice($offset, $length = null): CollectionInterface;

    /**
     * Chunk the underlying collection array.
     *
     * @param  int $size
     * @return CollectionInterface
     */
    public function chunk($size): CollectionInterface;

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param  int $offset
     * @param  int|null $length
     * @param  mixed $replacement
     * @return CollectionInterface
     */
    public function splice($offset, $length = null, $replacement = []): CollectionInterface;

    /**
     * Get the sum of the given values.
     *
     * @param  callable|string|null $callback
     * @return mixed
     */
    public function sum($callback = null): float;

    /**
     * Take the first or last {$limit} items.
     *
     * @param  int $limit
     * @return CollectionInterface
     */
    public function take($limit): CollectionInterface;

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function tap(callable $callback): CollectionInterface;

    /**
     * Transform each item in the collection using a callback.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function transform(callable $callback): CollectionInterface;

    /**
     * Return only unique items from the collection array.
     *
     * @param  string|callable|null $key
     * @param  bool $strict
     * @return CollectionInterface
     */
    public function unique($key = null, $strict = false): CollectionInterface;

    /**
     * Reset the keys on the underlying array.
     *
     * @return CollectionInterface
     */
    public function values(): CollectionInterface;

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param  mixed ...$items
     * @return CollectionInterface
     */
    public function zip($items): CollectionInterface;

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Convert the object into something JSON serializable.
     *
     * @return iterable
     */
    public function jsonSerialize(): iterable;

    /**
     * Get the collection of items as JSON.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0): string;

    /**
     * Get an iterator for the items.
     *
     * @return \Iterator
     */
    public function getIterator(): \Iterator;

    /**
     * Make a generator without the new keyword
     * @param array $data
     * @return CollectionInterface
     */
    public static function make($data = []): CollectionInterface;
}
