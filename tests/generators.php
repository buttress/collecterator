<?php

/**
 * This file contains generators we use for testing and for examples
 */

/**
 * Output
 * @param mixed $string
 */
function out($string)
{
    $strings = func_get_args();

    foreach ($strings as $arg) {
        echo (is_string($arg) ? $arg : json_encode($arg)) . PHP_EOL;
    }
}

/**
 * Stream the contents of a file
 * @param resource $resource
 * @param int $chunkSize The size of the chunk to read, if -1 will read until the next newline
 * @return \Generator
 */
function streamFile($resource, $chunkSize = -1)
{
    while (!feof($resource)) {
        if ($chunkSize === -1) {
            $chunk = fgets($resource);
        } else {
            $chunk = fread($resource, $chunkSize);
        }

        yield $chunk;
    }
}

/**
 * A generator that returns a list of numbers between $n1 and $n2
 *
 * @param float $n1 The number to start with
 * @param float $n2 The maximum number to yield, default: INF
 * @param float $step The step to take on each iteration, default: 1
 * @return \Generator
 */
function nToN($n1, $n2 = INF, $step = 1.0)
{
    $i = $n1;
    $conditional = function ($n1, $n2) {
        // If $n2 is INF, loop forever
        if ($n2 === INF) {
            return true;
        }

        // Otherwise loop until $n1 is more than $n2
        return $n1 <= $n2;
    };

    while ($conditional($i, $n2)) {
        yield $i;
        $i += $step;
    }
}

/**
 * Random numbers
 * @param int $count The amount of numbers
 * @param int $min
 * @param int $max
 * @return \Generator
 */
function randomNumbers($count = INF, $min = 0, $max = 100)
{
    if ($count === INF) {
        while (1) {
            yield mt_rand($min, $max);
        }
    }

    while ($count-- > 0) {
        yield mt_rand($min, $max);
    }
}

/**
 * The entire fibonacci sequence using bc_math
 * @param int $a
 * @param int $b
 * @param int $c
 * @return \Generator
 */
function fibonacci($a = 0, $b = 0, $c = 1)
{
    // convert input to strings
    $d = (string)$a;
    $e = (string)$b;
    $f = (string)$c;

    while (1) {
        yield $e;

        // Cache the old third value
        $g = $f;

        // Add the first two numbers to get the new third
        $f = bcadd($d, $e);

        // Shuffle the numbers
        $d = $e;
        $e = $g;
    }
}

/**
 * A generator that contains all primes starting from $start
 * @param int $start
 * @return \Generator
 */
function primes($start = 5)
{
    $i = $start;

    while ($i += 2) {
        $max = ceil(sqrt($i));
        $prime = true;
        while (--$max > 1) {
            $value = $i / $max;
            if (floor($value) === $value) {
                $prime = false;
                break;
            }
        }

        if ($prime) {
            yield $i;
        }
    }
}

/**
 * Reads from /dev/urandom
 * @param $length The amount of iterations
 * @param $size Amount of data to return in each iteration
 * @return \Generator
 */
function hugeDataStream($length, $size)
{
    $resource = fopen('/dev/urandom', 'r');
    $chunk = fread($resource, $size);

    fclose($resource);
    while ($length-- > 0) {
        yield $chunk;
    }

    yield 'finished';
}

/**
 * Sum up the numbers in a string
 * @param $input
 * @return float
 */
function stringSum($input)
{
    $sanitized = preg_replace(['~[^0-9]~', '~,{1,}~'], [',', ','], $input);
    $numbers = explode(',', trim($sanitized, ','));

    return array_sum(array_filter($numbers));
}
