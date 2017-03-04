<?php

namespace Buttress\Tests\Collection;

use Buttress\Collection\GeneratorCollection as C;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{

    public function testInfiniteData()
    {
        $values = C::make(fibonacci(1000, 5000, 10000));
        $keys = C::make(primes(100019));

        $this->assertSame([
            100109 => '21000',
            100129 => '31000',
            100151 => '37000'
        ], $keys->combine($values)->slice(5)->take(3)->all());
    }

    public function testLargeAmountOfData()
    {
        // Process in 100gb of data in 10mb chunks
        $stream = hugeDataStream(10000, 10000000);
        $bytes = 0;

        // Filter using a string match
        $result = C::make($stream)->filter(function ($item) use (&$bytes) {
            $bytes += strlen($item);
            return $item === 'finished';
        })->values();

        // Assert that the filter only returned the last item
        $this->assertSame(['finished'], $result->all());

        // Make sure we actually processed 100GB in that time
        $expectedSize = ($bytes - strlen('finished')) / 1000 / 1000 / 1000;
        $this->assertSame(100, $expectedSize);

    }
}
