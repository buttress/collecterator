<?php

namespace Buttress\Collection;

use ArrayAccess;
use ArrayIterator;
use Generator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Generator based collection
 */
class GeneratorCollection implements CollectionInterface, JsonSerializable
{

    /** @var Iterator|Generator */
    protected $generator;

    public function __construct($data = [])
    {
        if (is_array($data) || is_scalar($data) || is_null($data) || $data instanceof \stdClass) {
            $this->generator = new ArrayIterator((array) $data);
        } elseif ($data instanceof IteratorAggregate) {
            $this->generator = $data->getIterator();
        } elseif ($data instanceof Iterator) {
            $this->generator = $data;
        } elseif ($data instanceof Traversable) {
            $this->generator = $this->handleIterable($data);
        } elseif (function_exists('is_iterable') && is_iterable($data)) {
            $this->generator = $this->handleIterable($data);
        } else {
            throw new \InvalidArgumentException('Invalid value passed, must be an iterable or a scalar.');
        }
    }

    /**
     * Attempt to traverse a traversable
     * @param \traversable $item
     * @return \Generator
     */
    protected function handleIterable(iterable $item)
    {
        foreach ($item as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * Get the average value of a given key.
     *
     * @param  callable|string|null $callback
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function avg($callback = null): ?float
    {
        $sum = 0;
        $count = 0;
        foreach ($this->generator as $key => $item) {
            if ($callback) {
                $item = $this->accessKey($item, $callback, $key);
            }

            $sum += $item;
            $count++;
        }

        // Avoid division by 0
        if (!$count) {
            return null;
        }

        return $sum / $count;
    }

    /**
     * Alias for the "avg" method.
     *
     * @param  callable|string|null $callback
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function average($callback = null): ?float
    {
        return $this->avg($callback);
    }

    /**
     * Get the median of a given key.
     * Warning: This loads the full dataset into memory
     *
     * @param  null $key
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function median($key = null): ?float
    {
        $count = 0;
        $set = [];

        foreach ($this->generator as $givenKey => $item) {
            if ($key) {
                $item = $this->accessKey($item, $key, $givenKey);
            }

            $set[] = $item;
            $count++;
        }

        // Handle empty list
        if ($count === 0) {
            return null;
        }

        // Sort the full set
        sort($set);

        // If the count is divisible by 2, we'll need to get the average of the two
        if ($count && !($count % 2)) {
            $id = floor($count / 2) - 1;
            return ($set[(string)$id] + $set[(string)($id + 1)]) / 2;
        }

        return $set[$count / 2];
    }

    /**
     * Get the mode of a given key.
     *
     * @param  mixed $key
     * @return array|null
     * @throws \InvalidArgumentException
     */
    public function mode($key = null): ?array
    {
        /** @var array $result */
        $result = $this->reduce(function ($carry, $value, $valueKey) use ($key) {
            $item = $value;

            if ($key) {
                $item = $this->accessKey($value, $key, $valueKey);
            }

            if (!isset($carry[$item])) {
                $carry[$item] = 0;
            }

            $carry[$item]++;
            return $carry;
        }, []);

        if (!count($result)) {
            return null;
        }

        arsort($result);

        $output = [];
        $highestCount = 0;

        foreach ($result as $itemKey => $count) {
            if (!$highestCount) {
                $highestCount = $count;
            }

            if ($highestCount !== $count) {
                break;
            }

            $output[] = $itemKey;
        }

        return $output;
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return CollectionInterface
     */
    public function collapse(): CollectionInterface
    {
        return $this->flatten(1);
    }

    /**
     * Execute a callback over each item.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function each(callable $callback): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($callback) {
            $closed = false;
            foreach ($data as $key => $value) {
                if ($callback($value, $key) === false) {
                    $closed = true;
                }

                yield $key => $value;

                if ($closed) {
                    break;
                }
            }
        });
    }

    /**
     * Iterate over the generator without capturing the result
     * @return void
     */
    public function close(): void
    {
        while ($this->generator->valid()) {
            $this->generator->next();
        }
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param  mixed $keys
     * @return CollectionInterface
     */
    public function except($keys): CollectionInterface
    {
        if (!is_callable($keys)) {
            if (!is_array($keys)) {
                $keys = func_get_args();
            }
            $keys = function ($item, $key) use ($keys) {
                return in_array($key, $keys, false);
            };
        }

        return $this->wrap(function (Iterator $data) use ($keys) {
            foreach ($data as $key => $item) {
                if (!$keys($item, $key)) {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null $callback
     * @return CollectionInterface
     */
    public function filter(callable $callback = null): CollectionInterface
    {
        if (!$callback) {
            $callback = function ($item) {
                return (bool)$item;
            };
        }

        return $this->wrap(function (Iterator $data) use ($callback) {
            foreach ($data as $key => $item) {
                if ($callback($item, $key)) {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * Apply the callback if the value is truthy.
     *
     * @param  bool $value
     * @param  callable $callback
     * @return mixed
     */
    public function when($value, callable $callback): CollectionInterface
    {
        if ($value) {
            $this->generator = $callback($this)->generator;
        }

        return $this;
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  array $criteria Returns items that match all criteria
     * @param bool $strict
     * @return CollectionInterface
     */
    public function where(array $criteria, $strict = false): CollectionInterface
    {
        return array_reduce(
            array_keys($criteria),
            function (GeneratorCollection $collection, $key) use ($criteria, $strict) {
                return $collection->filter(function ($item) use ($key, $criteria, $strict) {
                    if ($strict) {
                        return $item[$key] === $criteria[$key];
                    }

                    return $item[$key] == $criteria[$key];
                });
            },
            $this
        );
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  string $key
     * @param  mixed $values
     * @param  bool $strict
     * @return CollectionInterface
     */
    public function whereIn($key, $values, $strict = false): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($key, $values, $strict) {
            foreach ($data as $itemKey => $item) {
                if (in_array($item[$key], $values, $strict)) {
                    yield $itemKey => $item;
                }
            }
        });
    }

    /**
     * Get the first item from the collection.
     *
     * @param  callable|null $callback
     * @param  mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        foreach ($this->generator as $item) {
            if (!$callback || $callback($item)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int $depth
     * @return CollectionInterface
     */
    public function flatten($depth = INF): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($depth) {
            $lowerDepth = $depth === INF ? INF : $depth - 1;
            foreach ($data as $key => $datum) {
                if ($lowerDepth >= 0 && is_iterable($datum)) {
                    $collection = $this::make($datum);

                    foreach ($collection->flatten($lowerDepth)->getIterator() as $item) {
                        yield $item;
                    }
                } else {
                    yield $datum;
                }
            }
        });
    }

    /**
     * Map then flatten
     * @param callable $callback
     * @param $depth
     * @return \Buttress\Collection\CollectionInterface
     */
    public function flatMap(callable $callback, $depth = INF): CollectionInterface
    {
        return $this->map($callback)->flatten($depth);
    }

    /**
     * Flip the items in the collection.
     *
     * @return CollectionInterface
     */
    public function flip(): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) {
            foreach ($data as $key => $item) {
                yield $item => $key;
            }
        });
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param  string|array $keys
     * @return CollectionInterface
     */
    public function forget($keys): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($keys) {
            foreach ($data as $key => $item) {
                if (is_array($keys)) {
                    if (in_array($key, $keys, false)) {
                        continue;
                    }
                } elseif ($keys === $key) {
                    continue;
                }

                yield $key => $item;
            }
        });
    }

    /**
     * Group an associative array by a field or using a callback.
     * Warning: This may require loading the entire data set into memory
     * To reduce memory usage, provide a limited batch size
     *
     * @param callable|string $groupBy
     * @param bool $preserveKeys
     * @param int $batch The size of the batch
     * @return \Buttress\Collection\CollectionInterface
     * @throws \InvalidArgumentException
     */
    public function groupBy($groupBy, $preserveKeys = false, $batch = INF): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($preserveKeys, $batch, $groupBy) {
            $count = 0;
            $groups = [];

            foreach ($data as $key => $datum) {
                $count++;

                // Fetch the key and make sure there's a group for it
                /** @var iterable $group */
                $group = $this->accessKey($datum, $groupBy, $key);

                if (!is_iterable($group)) {
                    $group = [$group];
                }

                foreach ($group as $groupName) {
                    if (!isset($groups[$groupName])) {
                        $groups[$groupName] = [];
                    }

                    // Add the data to the proper group
                    if ($preserveKeys) {
                        $groups[$groupName][$key] = $datum;
                    } else {
                        $groups[$groupName][] = $datum;
                    }
                }

                // Check batch, if we're full output and reset count
                if ($batch !== INF && $count > $batch) {
                    foreach ($groups as $group => $groupData) {
                        yield $group => $groupData;
                    }

                    $groups = [];
                    $count = 0;
                    unset($groupData, $group);
                }
            }

            // Yield the remaining batch
            foreach ($groups as $group => $groupData) {
                yield $group => $groupData;
            }
        });
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  callable|string $keyBy
     * @return CollectionInterface
     * @throws \InvalidArgumentException
     */
    public function keyBy($keyBy): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($keyBy) {
            foreach ($data as $key => $value) {
                yield $this->accessKey($value, $keyBy, $key) => $value;
            }
        });
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string $value
     * @param  string $glue
     * @return string
     */
    public function implode($value, $glue = null): string
    {
        return $this->reduce(function ($carry, $item) use ($value, $glue) {
            $currentGlue = $glue;
            if (is_string($item) && $currentGlue === null) {
                $currentGlue = $value;
            }
            if ($carry) {
                $carry .= $currentGlue;
            }

            if ($value && ($item instanceof ArrayAccess || is_array($item))) {
                $item = $item[$value];
            }

            return $carry . $item;
        }, '');
    }

    /**
     * Get the keys of the collection items.
     *
     * @return CollectionInterface
     */
    public function keys(): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) {
            foreach ($data as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * Return the last element
     *
     * @param  callable|null $callback if a callback is provided, we will filter the collection using that callback
     * @param  mixed $defualt
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        $collection = $callback ? $this->filter($callback) : $this;

        foreach ($collection->all() as $key => $value) {
            // Nothing needed here
        }

        return isset($value) ? $value : $default;
    }

    /**
     * Get the values of a given key.
     *
     * @param string $value
     * @param null $key
     * @return CollectionInterface
     * @throws \InvalidArgumentException
     */
    public function pluck($value, $key = null): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($value, $key) {
            foreach ($data as $dataKey => $datum) {
                if ($key) {
                    yield $this->accessKey($datum, $key) => $this->accessKey($datum, $value);
                } else {
                    yield $this->accessKey($datum, $value);
                }
            }
        });
    }

    /**
     * Get a value from a mixed key
     * @param mixed $mixed
     * @param mixed $key
     * @param null|string $givenKey
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private function accessKey($mixed, $key, $givenKey = null)
    {
        // Handle callable keys, just pass in the mixed and return the result.
        if (is_callable($key)) {
            return $key($mixed, $givenKey);
        }

        // If it's a string, an array, or accessible through []
        if (is_string($mixed) || is_array($mixed) || $mixed instanceof ArrayAccess) {
            return $mixed[$key];
        }

        // If it's an object
        if (is_object($mixed)) {
            return $mixed->{$key};
        }

        // Give up
        throw new InvalidArgumentException('Invalid object type provided, cannot access key.');
    }

    /**
     * Run a map over each of the items.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function map(callable $callback): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($callback) {
            foreach ($data as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function mapWithKeys(callable $callback): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($callback) {
            foreach ($data as $key => $value) {
                /** @var iterable $result */
                $result = $callback($value, $key);

                foreach ($result as $resultKey => $resultValue) {
                    yield $resultKey => $resultValue;
                }
            }
        });
    }

    /**
     * Get the max value of a given key.
     *
     * @param  callable|string|null $callback
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function max($callback = null)
    {
        return $this->reduce(function ($carry, $value, $key) use ($callback) {
            if ($callback === null) {
                $result = $value;
            } else {
                $result = $this->accessKey($value, $callback, $key);
            }

            if ($carry === null || $carry < $result) {
                return $result;
            }

            return $carry;
        }, null);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  mixed $items
     * @return CollectionInterface
     */
    public function merge($items): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($items) {
            yield from $data;

            if (is_iterable($items)) {
                yield from $items;
            } elseif ($items) {
                yield $items;
            }
        });
    }

    /**
     * Append items onto this collection
     *
     * @param mixed $items
     * @param bool $preserveKeys
     * @return \Buttress\Collection\CollectionInterface
     */
    public function append($items, bool $preserveKeys = true): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($items, $preserveKeys) {
            $lists = [$data, self::make($items)];

            foreach ($lists as $list) {
                foreach ($list as $key => $item) {
                    if ($preserveKeys) {
                        yield $key => $item;
                    } else {
                        yield $item;
                    }
                }
            }
        });
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param  mixed $values
     * @return CollectionInterface
     */
    public function combine($values): CollectionInterface
    {
        if ($values instanceof IteratorAggregate) {
            $values = $values->getIterator();
        } elseif (is_array($values)) {
            $values = new ArrayIterator($values);
        }

        return $this->wrap(function (Iterator $keys) use ($values) {
            foreach ($keys as $key) {
                yield $key => $values->current();
                $values->next();
            }
        });
    }

    /**
     * Get the min value of a given key.
     *
     * @param  callable|string|null $callback
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function min($callback = null)
    {
        return $this->reduce(function ($carry, $value, $key) use ($callback) {
            if ($callback === null) {
                $result = $value;
            } else {
                $result = $this->accessKey($value, $callback, $key);
            }

            // Ignore null
            if ($result === null) {
                return $carry;
            }

            // If carry is null, this is the first iteration
            if ($carry === null || $carry > $result) {
                return $result;
            }

            return $carry;
        }, null);
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int $step
     * @param  int $offset
     * @return CollectionInterface
     */
    public function nth($step, $offset = 0): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($step, $offset) {
            while ($offset && $data->valid()) {
                $data->next();
                $offset--;
            }

            while ($data->valid()) {
                yield $data->current();

                $skip = $step;
                while ($skip--) {
                    $data->next();
                }
            }
        });
    }

    /**
     * Get the items with the specified keys.
     *
     * @param  mixed $keys
     * @return CollectionInterface
     */
    public function only($keys): CollectionInterface
    {
        $args = func_get_args();
        if (count($args) === 1) {
            $args = reset($args);
        }

        if ($args === null) {
            return $this;
        }

        return $this->wrap(function (Iterator $data) use ($args) {
            foreach ($data as $key => $datum) {
                if (in_array($key, $args, false)) {
                    yield $key => $datum;
                }
            }
        });
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  int $page
     * @param  int $perPage
     * @return CollectionInterface
     * @throws \InvalidArgumentException
     */
    public function forPage(int $page, int $perPage): CollectionInterface
    {
        $page--;
        $offset = $page * $perPage;

        return $this->slice($offset, $perPage);
    }

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param  callable|string $callback
     * @return array [ $partition1, $partition2 ]
     * @throws \InvalidArgumentException
     */
    public function partition($callback): array
    {
        $partitions = [[], []];
        foreach ($this->generator as $key => $item) {
            if ($this->accessKey($item, $callback, $key)) {
                $partitions[0][$key] = $item;
            } else {
                $partitions[1][$key] = $item;
            }
        }

        return array_map(function ($value) {
            return $this::make($value);
        }, $partitions);
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param  callable $callback
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * @param  mixed $value
     * @param  mixed $passedKey
     * @return CollectionInterface
     */
    public function prepend($value, $passedKey = null): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($value, $passedKey) {
            if ($passedKey) {
                yield $passedKey => $value;
            } else {
                yield $value;
            }

            foreach ($data as $key => $value) {
                if ($passedKey) {
                    yield $key => $value;
                } else {
                    yield $value;
                }
            }
        });
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed $value
     * @return CollectionInterface
     */
    public function push($value): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($value) {
            foreach ($data as $key => $item) {
                yield $key => $item;
            }

            yield $value;
        });
    }

    /**
     * Put an item in the collection by key.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return CollectionInterface
     */
    public function put($key, $value): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($key, $value) {
            $replaced = false;

            foreach ($data as $dataKey => $datum) {
                if ($key === $dataKey) {
                    yield $key => $value;
                    $replaced = true;
                } else {
                    yield $dataKey => $datum;
                }
            }

            if (!$replaced) {
                yield $key => $value;
            }
        });
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable $callback
     * @param  mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        foreach ($this->generator as $key => $value) {
            $initial = $callback($initial, $value, $key);
        }

        return $initial;
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param  callable|mixed $callback
     * @return CollectionInterface
     * @throws \InvalidArgumentException
     */
    public function reject($callback): CollectionInterface
    {
        return $this->filter(function ($item, $key) use ($callback) {
            if (is_callable($callback)) {
                return !$callback($item, $key);
            }

            if (null === $item || is_scalar($item)) {
                return $item !== $callback;
            }

            return !$this->accessKey($item, $callback, $key);
        });
    }

    /**
     * Slice the underlying collection array.
     *
     * @param  int $offset
     * @param  int $length
     * @return CollectionInterface
     * @throws \InvalidArgumentException
     */
    public function slice($offset, $length = null): CollectionInterface
    {
        if ($length < 0) {
            throw new InvalidArgumentException('Negative slice lengths are not supported');
        }

        $result = $this;
        if ($offset < 0) {
            $result = $this->take($offset);
        } elseif ($offset > 0) {
            $result = $this->offset($offset);
        }

        if ($length !== null) {
            $result = $result->take($length);
        }

        return $result;
    }

    /**
     * Get items past an offset
     * @param $offset
     * @return CollectionInterface
     */
    private function offset($offset): CollectionInterface
    {
        return $this->wrap(function (Iterator $iterator) use ($offset) {
            while ($offset-- > 0 && $iterator->valid()) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();
                $iterator->next();
            }
        });
    }

    /**
     * Chunk the underlying collection array.
     *
     * @param  int $size
     * @return CollectionInterface
     */
    public function chunk($size): CollectionInterface
    {
        if (!($size > 0)) {
            return $this::make([]);
        }
        return $this->wrap(function (Iterator $data) use ($size) {
            $chunk = [];
            $count = 0;
            foreach ($data as $key => $item) {
                $chunk[$key] = $item;
                $count++;

                if ($count >= $size) {
                    yield $this::make($chunk);
                    $chunk = [];
                    $count = 0;
                }
            }

            if ($count) {
                yield $this::make($chunk);
            }
        });
    }

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param  int $offset
     * @param  int|null $length
     * @param  mixed $replacement
     * @return CollectionInterface
     */
    public function splice($offset, $length = null, $replacement = []): CollectionInterface
    {
        if (!is_iterable($replacement)) {
            $replacement = [$replacement];
        }

        return $this->wrap(function (Iterator $data) use ($offset, $length, $replacement) {
            // Handle offset
            while ($data->valid() && $offset) {
                yield $data->current();
                $data->next();
                $offset--;
            }

            // If we're given a null length, lets just remove the item at that point
            if ($length === null) {
                $length = 1;
            }

            // Skip $length
            while ($data->valid() && $length) {
                $data->next();
                $length--;
            }

            // Yield the replacements
            foreach ($replacement as $key => $item) {
                yield $item;
            }

            // Yield the remaining items
            while ($data->valid()) {
                yield $data->current();
                $data->next();
            }
        });
    }

    /**
     * Get the sum of the given values.
     *
     * @param  callable|string|null $callback
     * @return float
     * @throws \InvalidArgumentException
     */
    public function sum($callback = null): float
    {
        return $this->reduce(function ($carry, $value, $key) use ($callback) {
            $value = $callback === null ? $value : $this->accessKey($value, $callback, $key);
            return $carry + $value;
        }, 0);
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param  int $limit
     * @return CollectionInterface
     */
    public function take($limit): CollectionInterface
    {
        if ($limit >= 0) {
            return $this->takeFirst($limit);
        } else {
            return $this->takeLast(abs($limit));
        }
    }

    /**
     * Take items from the beginning of the collection
     *
     * @param int $count
     * @return \Buttress\Collection\CollectionInterface
     */
    public function takeFirst(int $count)
    {
        return $this->wrap(function (Iterator $data) use ($count) {
            $first = true;

            while ($count-- && $data->valid()) {
                if (!$first) {
                    $data->next();
                }

                yield $data->key() => $data->current();
                $first = false;
            }
        });
    }

    /**
     * Take items from the end of the collection
     *
     * @param int $count
     * @return \Buttress\Collection\CollectionInterface
     */
    public function takeLast(int $count)
    {
        return $this->wrap(function (Iterator $data) use ($count) {
            // From the end of the collection
            $limit = max(0, $count);
            $chunk = [];

            foreach ($data as $key => $datum) {
                $chunk[] = [$key, $datum];
                if (count($chunk) > $limit) {
                    array_shift($chunk);
                }
            }

            foreach ($chunk as $item) {
                [$key, $datum] = $item;
                yield $key => $datum;
            }
        });
    }

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function tap(callable $callback): CollectionInterface
    {
        $callback(clone $this);
        return $this;
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * @param  callable $callback
     * @return CollectionInterface
     */
    public function transform(callable $callback): CollectionInterface
    {
        return $this->map($callback);
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param  string|callable|null $key
     * @param  bool $strict
     * @return CollectionInterface
     */
    public function unique($key = null, $strict = false): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) use ($key, $strict) {
            $tracker = [];

            foreach ($data as $dataKey => $datum) {
                $compare = $datum;
                if ($key) {
                    $compare = is_callable($key) ? $key($datum, $dataKey) : $datum[$key];
                }

                if (!in_array($compare, $tracker, $strict)) {
                    yield $dataKey => $datum;
                    $tracker[] = $compare;
                }
            }
        });
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return CollectionInterface
     */
    public function values(): CollectionInterface
    {
        return $this->wrap(function (Iterator $data) {
            foreach ($data as $item) {
                yield $item;
            }
        });
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param  mixed ...$items
     * @return CollectionInterface
     */
    public function zip($items): CollectionInterface
    {
        $items = array_map(function ($items) {
            if ($items instanceof IteratorAggregate) {
                return $items->getIterator();
            }

            if (is_array($items)) {
                return new ArrayIterator($items);
            }

            return $items;
        }, func_get_args());

        return $this->wrap(function (Iterator $data) use ($items) {
            foreach ($data as $key => $datum) {
                $result = [$datum];

                /** @var Iterator $item */
                foreach ($items as $item) {
                    $result[] = $item->current();
                    $item->next();
                }

                yield $key => $this::make($result);
            }
        });
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $generator = function (Iterator $data) {
            foreach ($data as $key => $item) {
                if ($item instanceof JsonSerializable) {
                    $item = $item->jsonSerialize();
                }
                yield $key => $item;
            }
        };

        return iterator_to_array($generator($this->generator));
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return iterable
     */
    public function jsonSerialize(): iterable
    {
        return $this->toArray();
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Get an iterator for the items.
     *
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        return $this->generator;
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Wrap this generator with a new generator
     * @param callable $generatorCallable
     * @return CollectionInterface
     */
    private function wrap(callable $generatorCallable): CollectionInterface
    {
        return $this::make($generatorCallable($this->generator, $this));
    }

    /**
     * Make a collection without the new keyword
     * @param array $data
     * @return CollectionInterface
     */
    public static function make($data = []): CollectionInterface
    {
        return new self($data);
    }
}
