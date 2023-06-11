<?php

namespace Buttress\Tests\Collection;

use ArrayAccess;
use Buttress\Collection\GeneratorCollection as C;
use JsonSerializable;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class SupportCollectionTest
 * These tests were copied from the illuminate collection package and modified slightly to test this generator library
 */
class GeneratorCollectionTest extends TestCase
{
    public function testFirstReturnsFirstItemInCollection()
    {
        $c = C::make(['foo', 'bar']);
        $this->assertEquals('foo', $c->first());
    }

    public function testFirstWithCallback()
    {
        $data = C::make(['foo', 'bar', 'baz']);
        $result = $data->first(function ($value) {
            return $value === 'bar';
        });
        $this->assertEquals('bar', $result);
    }

    public function testFirstWithCallbackAndDefault()
    {
        $data = C::make(['foo', 'bar']);
        $result = $data->first(function ($value) {
            return $value === 'baz';
        }, 'default');
        $this->assertEquals('default', $result);
    }

    public function testFirstWithDefaultAndWithoutCallback()
    {
        $data = C::make();
        $result = $data->first(null, 'default');
        $this->assertEquals('default', $result);
    }

    public function testLastReturnsLastItemInCollection()
    {
        $c = C::make(['foo', 'bar']);
        $this->assertEquals('bar', $c->last());
    }

    public function testLastWithCallback()
    {
        $data = C::make([100, 200, 300]);
        $result = $data->last(function ($value) {
            return $value < 250;
        });
        $this->assertEquals(200, $result);

        $data = C::make([100, 200, 300]);
        $result = $data->last(function ($value, $key) {
            return $key < 2;
        });
        $this->assertEquals(200, $result);
    }

    public function testLastWithCallbackAndDefault()
    {
        $data = C::make(['foo', 'bar']);
        $result = $data->last(function ($value) {
            return $value === 'baz';
        }, 'default');
        $this->assertEquals('default', $result);
    }

    public function testLastWithDefaultAndWithoutCallback()
    {
        $data = C::make();
        $this->assertEquals('default', $data->last(null, 'default'));
    }

    public function testCollectionIsConstructed()
    {
        $collection = C::make('foo');
        $this->assertSame(['foo'], $collection->all());

        $collection = C::make(2);
        $this->assertSame([2], $collection->all());

        $collection = C::make(false);
        $this->assertSame([false], $collection->all());

        $collection = C::make(null);
        $this->assertSame([], $collection->all());

        $collection = C::make();
        $this->assertSame([], $collection->all());
    }

    public function testToArrayCallsToArrayOnEachItemInCollection()
    {
        $item1 = m::mock(JsonSerializable::class);
        $item1->shouldReceive('jsonSerialize')->once()->andReturn('foo.array');
        $c = C::make([$item1, 'bar.array']);
        $results = $c->toArray();

        $this->assertEquals(['foo.array', 'bar.array'], $results);
    }

    public function testJsonSerializeCallsToArrayOrJsonSerializeOnEachItemInCollection()
    {
        $item1 = m::mock('JsonSerializable');
        $item1->shouldReceive('jsonSerialize')->once()->andReturn('foo.json');
        $c = C::make([$item1, 'bar.array']);
        $results = $c->jsonSerialize();

        $this->assertEquals(['foo.json', 'bar.array'], $results);
    }

    public function testToJsonEncodesTheJsonSerializeResult()
    {
        $c = $this->getMockBuilder(C::class)->onlyMethods(['jsonSerialize'])->getMock();
        $c->expects($this->once())->method('jsonSerialize')->will($this->returnValue(['foo']));
        $results = $c->toJson();

        $this->assertJsonStringEqualsJsonString(json_encode(['foo']), $results);
    }

    public function testCastingToStringJsonEncodesTheToArrayResult()
    {
        $c = $this->getMockBuilder(C::class)->onlyMethods(['jsonSerialize'])->getMock();
        $c->expects($this->once())->method('jsonSerialize')->will($this->returnValue(['foo']));

        $this->assertJsonStringEqualsJsonString(json_encode(['foo']), (string)$c);
    }

    public function testForgetSingleKey()
    {
        $c = C::make(['foo', 'bar']);
        $c = $c->forget(0);
        $this->assertSame([1 => 'bar'], $c->toArray());

        $c = C::make(['foo' => 'bar', 'baz' => 'qux']);
        $c = $c->forget('foo');
        $this->assertSame(['baz' => 'qux'], $c->toArray());
    }

    public function testForgetArrayOfKeys()
    {
        $c = C::make(['foo', 'bar', 'baz']);
        $c = $c->forget([0, 2])->toArray();
        $this->assertFalse(isset($c[0]));
        $this->assertFalse(isset($c[2]));
        $this->assertTrue(isset($c[1]));

        $c = C::make(['name' => 'taylor', 'foo' => 'bar', 'baz' => 'qux']);
        $c = $c->forget(['foo', 'baz'])->toArray();
        $this->assertFalse(isset($c['foo']));
        $this->assertFalse(isset($c['baz']));
        $this->assertTrue(isset($c['name']));
    }

    public function testIterable()
    {
        $c = C::make(['foo']);
        $this->assertInstanceOf('ArrayIterator', $c->getIterator());
        $this->assertEquals(['foo'], $c->getIterator()->getArrayCopy());
    }

    public function testFilter()
    {
        $c = C::make([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertSame([1 => ['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->all());

        $c = C::make(['', 'Hello', '', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->filter()->values()->toArray());

        $c = C::make(['id' => 1, 'first' => 'Hello', 'second' => 'World']);
        $this->assertEquals(['first' => 'Hello', 'second' => 'World'], $c->filter(function ($item, $key) {
            return $key != 'id';
        })->all());
    }

    public function testWhere()
    {
        $c = C::make([
            ['v' => 1, 'g' => 'a', 's' => 40],
            ['v' => 2, 'g' => 'a', 's' => 40],
            ['v' => 3, 'g' => 'a', 's' => 40],
            ['v' => '3', 'g' => 'v', 's' => 40],
            ['v' => 4, 'g' => 'v', 's' => 40]
        ]);

        $this->assertSame([
            ['v' => 3, 'g' => 'a', 's' => 40],
            ['v' => '3', 'g' => 'v', 's' => 40]
        ],
            $c->where([
                'v' => 3,
                's' => 40
            ])->values()->toArray());
    }

    public function testWhereStrict()
    {
        $c = C::make([
            ['v' => 1, 'g' => 'a', 's' => 40],
            ['v' => 2, 'g' => 'a', 's' => 40],
            ['v' => 3, 'g' => 'a', 's' => 40],
            ['v' => '3', 'g' => 'v', 's' => 40],
            ['v' => 4, 'g' => 'v', 's' => 40]
        ]);

        $this->assertSame([['v' => 3, 'g' => 'a', 's' => 40]],
            $c->where([
                'v' => 3,
                's' => 40
            ], true)->values()->toArray());
    }

    public function testWhereIn()
    {
        $c = C::make([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 1], ['v' => 3], ['v' => '3']], $c->whereIn('v', [1, 3])->values()->all());
    }

    public function testWhereInStrict()
    {
        $c = C::make([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 1], ['v' => 3]], $c->whereIn('v', [1, 3], true)->values()->all());
    }

    public function testValues()
    {
        $c = C::make([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertEquals([['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->values()->all());
    }

    public function testFlatten()
    {
        // Flat arrays are unaffected
        $c = C::make(['#foo', '#bar', '#baz']);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays are flattened with existing flat items
        $c = C::make([['#foo', '#bar'], '#baz']);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Sets of nested arrays are flattened
        $c = C::make([['#foo', '#bar'], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Deeply nested arrays are flattened
        $c = C::make([['#foo', ['#bar']], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested collections are flattened alongside arrays
        $c = C::make([C::make(['#foo', '#bar']), ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested collections containing plain arrays are flattened
        $c = C::make([C::make(['#foo', ['#bar']]), ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays containing collections are flattened
        $c = C::make([['#foo', C::make(['#bar'])], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays containing collections containing arrays are flattened
        $c = C::make([['#foo', C::make(['#bar', ['#zap']])], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#zap', '#baz'], $c->flatten()->all());
    }

    public function testFlattenWithDepth()
    {
        // No depth flattens recursively
        $c = C::make([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], $c->flatten()->all());

        // Specifying a depth only flattens to that depth
        $c = C::make([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', ['#bar', ['#baz']], '#zap'], $c->flatten(1)->all());

        $c = C::make([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', '#bar', ['#baz'], '#zap'], $c->flatten(2)->all());
    }

    public function testFlattenIgnoresKeys()
    {
        // No depth ignores keys
        $c = C::make(['#foo', ['key' => '#bar'], ['key' => '#baz'], 'key' => '#zap']);
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], $c->flatten()->all());

        // Depth of 1 ignores keys
        $c = C::make(['#foo', ['key' => '#bar'], ['key' => '#baz'], 'key' => '#zap']);
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], $c->flatten(1)->all());
    }

    public function testMergeNull()
    {
        $c = C::make(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello'], $c->merge(null)->all());
    }

    public function testMergeArray()
    {
        $c = C::make(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello', 'id' => 1], $c->merge(['id' => 1])->all());
    }

    public function testMergeCollection()
    {
        $c = C::make(['name' => 'Hello']);
        $this->assertEquals(['name' => 'World', 'id' => 1], $c->merge(C::make(['name' => 'World', 'id' => 1]))->all());
    }

    public function testAppend()
    {
        $c = C::make(['name' => 'Hello', 'zz' => 'test']);

        // Test PreserveKeys
        $this->assertEquals(['name' => 'herpderp', 'zz' => 'test'], $c->append(['name' => 'herpderp'])->all());

        // Test no keys
        $this->assertEquals([
            'Hello',
            'test',
            'herpderp'
        ], $c->append(['name' => 'herpderp'], false)->all());
    }

    public function testEach()
    {
        $result = [];
        $c = C::make($original = [1, 2, 'foo' => 'bar', 'bam' => 'baz']);
        $c->each(function ($item, $key) use (&$result) {
            $result[$key] = $item;
        })->close();
        $this->assertEquals($original, $result);

        $result = [];
        $c = C::make($original = [1, 2, 'foo' => 'bar', 'bam' => 'baz']);
        $c->each(function ($item, $key) use (&$result) {
            $result[$key] = $item;
            if (is_string($key)) {
                return false;
            }
        })->close();
        $this->assertEquals([1, 2, 'foo' => 'bar'], $result);
    }

    public function testEachDoesntAddExtraIteration()
    {
        ob_start();

        C::make(nToN(0, 9))->each(function () {
            echo '1';
        })->close();

        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(str_repeat('1', 10), $result);
    }

    public function testEachFiresOnResolution()
    {
        $fired = 0;
        $collection = C::make(nToN(0, 9))->each(function () use (&$fired) {
            $fired++;
        });

        $collection->first();
        $this->assertEquals(1, $fired);
    }

    public function testUnique()
    {
        $c = C::make(['Hello', 'World', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->unique()->all());

        $c = C::make([[1, 2], [1, 2], [2, 3], [3, 4], [2, 3]]);
        $this->assertEquals([[1, 2], [2, 3], [3, 4]], $c->unique()->values()->all());
    }

    public function testUniqueWithCallback()
    {
        $testArray = [
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            2 => ['id' => 2, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
            4 => ['id' => 4, 'first' => 'Abigail', 'last' => 'Otwell'],
            5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'],
            6 => ['id' => 6, 'first' => 'Taylor', 'last' => 'Swift'],
        ];;

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
        ], (C::make($testArray))->unique('first')->all());

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
            5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'],
        ], (C::make($testArray))->unique(function ($item) {
            return $item['first'] . $item['last'];
        })->all());

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            2 => ['id' => 2, 'first' => 'Taylor', 'last' => 'Otwell'],
        ], (C::make($testArray))->unique(function ($item, $key) {
            return $key % 2;
        })->all());
    }

    public function testUniqueStrict()
    {
        $c = C::make([
            [
                'id' => '0',
                'name' => 'zero',
            ],
            [
                'id' => '00',
                'name' => 'double zero',
            ],
            [
                'id' => '0',
                'name' => 'again zero',
            ],
        ]);

        $this->assertEquals([
            [
                'id' => '0',
                'name' => 'zero',
            ],
            [
                'id' => '00',
                'name' => 'double zero',
            ],
        ], $c->unique('id', true)->all());
    }

    public function testCollapse()
    {
        $data = C::make([[$object1 = new StdClass], [$object2 = new StdClass]]);
        $this->assertEquals([$object1, $object2], $data->collapse()->all());
    }

    public function testCollapseWithNestedCollactions()
    {
        $data = C::make([C::make([1, 2, 3]), C::make([4, 5, 6])]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $data->collapse()->all());
    }

    public function testFlip()
    {
        $data = C::make(['name' => 'taylor', 'framework' => 'laravel']);
        $this->assertEquals(['taylor' => 'name', 'laravel' => 'framework'], $data->flip()->toArray());
    }

    public function testChunk()
    {
        $data = C::make([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $data = $data->chunk(3);
        $dataArray = $data->all();

        $this->assertInstanceOf(C::class, $data);
        $this->assertInstanceOf(C::class, $dataArray[0]);
        $this->assertCount(4, $dataArray);
        $this->assertEquals([1, 2, 3], $dataArray[0]->toArray());
        $this->assertEquals([9 => 10], $dataArray[3]->toArray());
    }

    public function testChunkWhenGivenZeroAsSize()
    {
        $collection = C::make([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertEquals(
            [],
            $collection->chunk(0)->toArray()
        );
    }

    public function testChunkWhenGivenLessThanZero()
    {
        $collection = C::make([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertEquals(
            [],
            $collection->chunk(-1)->toArray()
        );
    }

    public function testExcept()
    {
        $testData = ['first' => 'Taylor', 'last' => 'Otwell', 'email' => 'taylorotwell@gmail.com'];

        $this->assertEquals(['first' => 'Taylor'], (C::make($testData))->except(['last', 'email', 'missing'])->all());
        $this->assertEquals(['first' => 'Taylor'], (C::make($testData))->except('last', 'email', 'missing')->all());

        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'],
            (C::make($testData))->except(['last'])->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'],
            (C::make($testData))->except('last')->all());
    }

    public function testPluckWithArrayAndObjectValues()
    {
        $testData = [(object)['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']];

        $this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], (C::make($testData))->pluck('email', 'name')->all());
        $this->assertEquals(['foo', 'bar'], (C::make($testData))->pluck('email')->all());
    }

    public function testPluckWithArrayAccessValues()
    {
        $testData = [
            new TestArrayAccessImplementation(['name' => 'taylor', 'email' => 'foo']),
            new TestArrayAccessImplementation(['name' => 'dayle', 'email' => 'bar']),
        ];

        $this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], (C::make($testData))->pluck('email', 'name')->all());
        $this->assertEquals(['foo', 'bar'], (C::make($testData))->pluck('email')->all());
    }

    public function testImplode()
    {
        $data = C::make([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
        $this->assertEquals('foobar', $data->implode('email'));
        $this->assertEquals('foo,bar', $data->implode('email', ','));

        $data = C::make(['taylor', 'dayle']);
        $this->assertEquals('taylordayle', $data->implode(''));
        $this->assertEquals('taylor,dayle', $data->implode(','));
    }

    public function testTake()
    {
        $data = C::make(['taylor', 'dayle', 'shawn']);
        $data = $data->take(2);
        $this->assertSame(['taylor', 'dayle'], $data->all());
    }

    public function testTakeDoesntIterateExtra()
    {
        $count = 0;
        $data = C::make(nToN(10, 100))->each(function () use (&$count) {
            $count++;
        });

        $data->take(1)->all();
        $this->assertEquals(1, $count);
    }

    public function testTakeLast()
    {
        $data = C::make(['taylor', 'dayle', 'shawn']);
        $data = $data->take(-2);
        $this->assertEquals([1 => 'dayle', 2 => 'shawn'], $data->all());
    }

    public function testMakeMethod()
    {
        // Test String
        $this->assertEquals(['foo'], C::make('foo')->all());

        // Test null
        $this->assertEquals([], C::make(null)->all());

        // Test int
        $this->assertEquals([1], C::make(1)->all());

        // Test float
        $this->assertEquals([1.1], C::make(1.1)->all());

        // Test array
        $array = ['foo', 'bar'];
        $this->assertEquals($array, C::make($array)->all());

        $associative = ['foo' => 'a', 'bar' => 'b'];
        $this->assertEquals($associative, C::make($associative)->all());

        // Test object
        $this->assertEquals($array, C::make((object)$array)->all());
        $this->assertEquals($associative, C::make((object)$associative)->all());

        // Test Generator
        $this->assertEquals($array, C::make($this->generator($array))->all());
        $this->assertEquals($associative, C::make($this->generator($associative))->all());

        // Test ArrayIterator
        $iterator = new \ArrayIterator($array);
        $this->assertEquals($array, C::make($iterator)->all());

        // Test IteratorAggregate
        $aggregate = new Class($iterator) implements \IteratorAggregate
        {
            private $iterator;

            public function __construct(\Iterator $iterator)
            {
                $this->iterator = $iterator;
            }

            public function getIterator(): \Iterator
            {
                return $this->iterator;
            }
        };
        $this->assertEquals($array, C::make($aggregate)->all());

        // Test irregular iterable
        $dom = new \DOMDocument();
        $dom->loadHTML('<div><div></div><div></div><div></div></div>');
        $this->assertEquals(iterator_to_array($dom->childNodes), C::make($dom->childNodes)->all());

        // Test collection
        $this->assertEquals($array, C::make(C::make($array))->all());
        $this->assertEquals($associative, C::make(C::make($associative))->all());
    }

    private function generator(iterable $items)
    {
        yield from $items;
    }

    public function testMakeMethodFromNull()
    {
        $collection = C::make(null);
        $this->assertEquals([], $collection->all());

        $collection = C::make();
        $this->assertEquals([], $collection->all());
    }

    public function testMakeMethodFromCollection()
    {
        $firstCollection = C::make(['foo' => 'bar']);
        $secondCollection = C::make($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }

    public function testMakeMethodFromArray()
    {
        $collection = C::make(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testConstructMakeFromObject()
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $collection = C::make($object);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testConstructMethod()
    {
        $collection = C::make('foo');
        $this->assertEquals(['foo'], $collection->all());
    }

    public function testConstructMethodFromNull()
    {
        $collection = C::make(null);
        $this->assertEquals([], $collection->all());

        $collection = C::make();
        $this->assertEquals([], $collection->all());
    }

    public function testConstructMethodFromCollection()
    {
        $firstCollection = C::make(['foo' => 'bar']);
        $secondCollection = C::make($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }

    public function testConstructMethodFromArray()
    {
        $collection = C::make(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testConstructMethodFromObject()
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $collection = C::make($object);
        $this->assertEquals(['foo' => 'bar'], $collection->all());
    }

    public function testSplice()
    {
        $data = C::make(['foo', 'baz']);
        $data = $data->splice(1);
        $this->assertEquals(['foo'], $data->all());

        $data = C::make(['foo', 'baz']);
        $data = $data->splice(1, 0, 'bar');
        $this->assertEquals(['foo', 'bar', 'baz'], $data->all());

        $data = C::make(['foo', 'baz']);
        $data = $data->splice(1, 1);
        $this->assertEquals(['foo'], $data->all());
    }

    public function testGetPluckValueWithAccessors()
    {
        $model = new TestAccessorEloquentTestStub(['some' => 'foo']);
        $modelTwo = new TestAccessorEloquentTestStub(['some' => 'bar']);
        $data = C::make([$model, $modelTwo]);

        $this->assertEquals(['foo', 'bar'], $data->pluck('some')->all());
    }

    public function testMap()
    {
        $data = C::make(['first' => 'taylor', 'last' => 'otwell']);
        $data = $data->map(function ($item, $key) {
            return $key . '-' . strrev($item);
        });
        $this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
    }

    public function testFlatMap()
    {
        $data = C::make([
            ['name' => 'taylor', 'hobbies' => ['programming', 'basketball']],
            ['name' => 'adam', 'hobbies' => ['music', 'powerlifting']],
        ]);
        $data = $data->flatMap(function ($person) {
            return $person['hobbies'];
        });
        $this->assertEquals(['programming', 'basketball', 'music', 'powerlifting'], $data->all());
    }

    public function testMapWithKeys()
    {
        $data = C::make([
            ['name' => 'Blastoise', 'type' => 'Water', 'idx' => 9],
            ['name' => 'Charmander', 'type' => 'Fire', 'idx' => 4],
            ['name' => 'Dragonair', 'type' => 'Dragon', 'idx' => 148],
        ]);
        $data = $data->mapWithKeys(function ($pokemon) {
            return [$pokemon['name'] => $pokemon['type']];
        });
        $this->assertEquals(
            ['Blastoise' => 'Water', 'Charmander' => 'Fire', 'Dragonair' => 'Dragon'],
            $data->all()
        );
    }

    public function testMapWithKeysIntegerKeys()
    {
        $data = C::make([
            ['id' => 1, 'name' => 'A'],
            ['id' => 3, 'name' => 'B'],
            ['id' => 2, 'name' => 'C'],
        ]);
        $data = $data->mapWithKeys(function ($item) {
            return [$item['id'] => $item];
        });
        $this->assertSame(
            [1, 3, 2],
            $data->keys()->all()
        );
    }

    public function testMapWithKeysMultipleRows()
    {
        $data = C::make([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
        ]);
        $data = $data->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name'], $item['name'] => $item['id']];
        });
        $this->assertSame(
            [
                1 => 'A',
                'A' => 1,
                2 => 'B',
                'B' => 2,
                3 => 'C',
                'C' => 3,
            ],
            $data->all()
        );
    }

    public function testMapWithKeysCallbackKey()
    {
        $data = C::make([
            3 => ['id' => 1, 'name' => 'A'],
            5 => ['id' => 3, 'name' => 'B'],
            4 => ['id' => 2, 'name' => 'C'],
        ]);
        $data = $data->mapWithKeys(function ($item, $key) {
            return [$key => $item['id']];
        });
        $this->assertSame(
            [3, 5, 4],
            $data->keys()->all()
        );
    }

    public function testNth()
    {
        $testArray = [
            6 => 'a',
            4 => 'b',
            7 => 'c',
            1 => 'd',
            5 => 'e',
            3 => 'f',
        ];

        $this->assertEquals(['a', 'e'], (C::make($testArray))->nth(4)->all());
        $this->assertEquals(['b', 'f'], (C::make($testArray))->nth(4, 1)->all());
        $this->assertEquals(['c'], (C::make($testArray))->nth(4, 2)->all());
        $this->assertEquals(['d'], (C::make($testArray))->nth(4, 3)->all());
    }

    public function testTransform()
    {
        $data = C::make(['first' => 'taylor', 'last' => 'otwell']);
        $data = $data->transform(function ($item, $key) {
            return $key . '-' . strrev($item);
        });
        $this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
    }

    public function testGroupByAttribute()
    {
        $data = C::make([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy('rating');
        $this->assertEquals([
            1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']],
            2 => [['rating' => 2, 'url' => '2']]
        ], $result->toArray());

        $data = C::make([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy('url');
        $this->assertEquals([
            1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']],
            2 => [['rating' => 2, 'url' => '2']]
        ], $result->toArray());
    }

    public function testGroupByAttributePreservingKeys()
    {
        $data = C::make([
            10 => ['rating' => 1, 'url' => '1'],
            20 => ['rating' => 1, 'url' => '1'],
            30 => ['rating' => 2, 'url' => '2']
        ]);

        $result = $data->groupBy('rating', true);

        $expected_result = [
            1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
            2 => [30 => ['rating' => 2, 'url' => '2']],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveSingleGroup()
    {
        $data = C::make([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy(function ($item) {
            return $item['rating'];
        });

        $this->assertEquals([
            1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']],
            2 => [['rating' => 2, 'url' => '2']]
        ], $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveSingleGroupPreservingKeys()
    {
        $data = C::make([
            10 => ['rating' => 1, 'url' => '1'],
            20 => ['rating' => 1, 'url' => '1'],
            30 => ['rating' => 2, 'url' => '2']
        ]);

        $result = $data->groupBy(function ($item) {
            return $item['rating'];
        }, true);

        $expected_result = [
            1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
            2 => [30 => ['rating' => 2, 'url' => '2']],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveMultipleGroups()
    {
        $data = C::make([
            ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ['user' => 3, 'roles' => ['Role_1']],
        ]);

        $result = $data->groupBy(function ($item) {
            return $item['roles'];
        });

        $expected_result = [
            'Role_1' => [
                ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
                ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
                ['user' => 3, 'roles' => ['Role_1']],
            ],
            'Role_2' => [
                ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ],
            'Role_3' => [
                ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testGroupByClosureWhereItemsHaveMultipleGroupsPreservingKeys()
    {
        $data = C::make([
            10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            30 => ['user' => 3, 'roles' => ['Role_1']],
        ]);

        $result = $data->groupBy(function ($item) {
            return $item['roles'];
        }, true);

        $expected_result = [
            'Role_1' => [
                10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
                20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
                30 => ['user' => 3, 'roles' => ['Role_1']],
            ],
            'Role_2' => [
                20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ],
            'Role_3' => [
                10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    public function testKeyByAttribute()
    {
        $data = C::make([
            ['rating' => 1, 'name' => '1'],
            ['rating' => 2, 'name' => '2'],
            ['rating' => 3, 'name' => '3']
        ]);

        $result = $data->keyBy('rating');
        $this->assertEquals([
            1 => ['rating' => 1, 'name' => '1'],
            2 => ['rating' => 2, 'name' => '2'],
            3 => ['rating' => 3, 'name' => '3']
        ], $result->all());

        $data = C::make([
            ['rating' => 1, 'name' => '1'],
            ['rating' => 2, 'name' => '2'],
            ['rating' => 3, 'name' => '3']
        ]);

        $result = $data->keyBy(function ($item) {
            return $item['rating'] * 2;
        });
        $this->assertEquals([
            2 => ['rating' => 1, 'name' => '1'],
            4 => ['rating' => 2, 'name' => '2'],
            6 => ['rating' => 3, 'name' => '3']
        ], $result->all());
    }

    public function testKeyByClosure()
    {
        $data = C::make([
            ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ]);
        $result = $data->keyBy(function ($item, $key) {
            return strtolower($key . '-' . $item['firstname'] . $item['lastname']);
        });
        $this->assertEquals([
            '0-taylorotwell' => ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            '1-lucasmichot' => ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ], $result->all());
    }

    public function testGettingSumFromCollection()
    {
        $c = C::make([(object)['foo' => 50], (object)['foo' => 50]]);
        $this->assertEquals(100, $c->sum('foo'));

        $c = C::make([(object)['foo' => 50], (object)['foo' => 50]]);
        $this->assertEquals(100, $c->sum(function ($i) {
            return $i->foo;
        }));
    }

    public function testCanSumValuesWithoutACallback()
    {
        $c = C::make([1, 2, 3, 4, 5]);
        $this->assertEquals(15, $c->sum());
    }

    public function testGettingSumFromEmptyCollection()
    {
        $c = C::make();
        $this->assertEquals(0, $c->sum('foo'));
    }

    public function testRejectRemovesElementsPassingTruthTest()
    {
        $c = C::make(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject('bar')->values()->all());

        $c = C::make(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject(function ($v) {
            return $v == 'bar';
        })->values()->all());

        $c = C::make(['foo', null]);
        $this->assertEquals(['foo'], $c->reject(null)->values()->all());

        $c = C::make(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject('baz')->values()->all());

        $c = C::make(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject(function ($v) {
            return $v == 'baz';
        })->values()->all());

        $c = C::make(['id' => 1, 'primary' => 'foo', 'secondary' => 'bar']);
        $this->assertEquals(['primary' => 'foo', 'secondary' => 'bar'], $c->reject(function ($item, $key) {
            return $key == 'id';
        })->all());
    }

    public function testKeys()
    {
        $c = C::make(['name' => 'taylor', 'framework' => 'laravel']);
        $this->assertEquals(['name', 'framework'], $c->keys()->all());
    }

    public function testPaginate()
    {
        $c = C::make(['one', 'two', 'three', 'four']);
        $this->assertSame(['one', 'two'], $c->forPage(1, 2)->all());

        $c = C::make(['one', 'two', 'three', 'four']);
        $this->assertSame([2 => 'three', 3 => 'four'], $c->forPage(2, 2)->all());

        $c = C::make(['one', 'two', 'three', 'four']);
        $this->assertSame([], $c->forPage(3, 2)->all());
    }

    public function testPrepend()
    {
        $c = C::make(['one', 'two', 'three', 'four']);
        $this->assertEquals(['zero', 'one', 'two', 'three', 'four'], $c->prepend('zero')->all());

        $c = C::make(['one' => 1, 'two' => 2]);
        $this->assertEquals(['zero' => 0, 'one' => 1, 'two' => 2], $c->prepend(0, 'zero')->all());
    }

    public function testZip()
    {
        $c = C::make([1, 2, 3]);
        $c = $c->zip(C::make([4, 5, 6]))->all();
        $this->assertInstanceOf(C::class, $c[0]);
        $this->assertInstanceOf(C::class, $c[1]);
        $this->assertInstanceOf(C::class, $c[2]);
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4], $c[0]->all());
        $this->assertEquals([2, 5], $c[1]->all());
        $this->assertEquals([3, 6], $c[2]->all());

        $c = C::make([1, 2, 3]);
        $c = $c->zip([4, 5, 6], [7, 8, 9])->all();
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4, 7], $c[0]->all());
        $this->assertEquals([2, 5, 8], $c[1]->all());
        $this->assertEquals([3, 6, 9], $c[2]->all());

        $c = C::make([1, 2, 3]);
        $c = $c->zip([4, 5, 6], [7])->all();
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4, 7], $c[0]->all());
        $this->assertEquals([2, 5, null], $c[1]->all());
        $this->assertEquals([3, 6, null], $c[2]->all());
    }

    public function testGettingMaxItemsFromCollection()
    {
        $c = C::make([(object)['foo' => 10], (object)['foo' => 20]]);
        $this->assertEquals(20, $c->max(function ($item) {
            return $item->foo;
        }));
        $this->assertEquals(20, $c->max('foo'));

        $c = C::make([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(20, $c->max('foo'));

        $c = C::make([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $c->max());

        $c = C::make();
        $this->assertNull($c->max());
    }

    public function testGettingMinItemsFromCollection()
    {
        $c = C::make([(object)['foo' => 10], (object)['foo' => 20]]);
        $this->assertEquals(10, $c->min(function ($item) {
            return $item->foo;
        }));

        $c = C::make([(object)['foo' => 10], (object)['foo' => 20]]);
        $this->assertEquals(10, $c->min('foo'));

        $c = C::make([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(10, $c->min('foo'));

        $c = C::make([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $c->min());

        $c = C::make([1, null, 3, 4, 5]);
        $this->assertEquals(1, $c->min());

        $c = C::make([0, 1, 2, 3, 4]);
        $this->assertEquals(0, $c->min());

        $c = C::make();
        $this->assertNull($c->min());
    }

    public function testOnly()
    {
        $data = ['first' => 'Taylor', 'last' => 'Otwell', 'email' => 'taylorotwell@gmail.com'];

        $this->assertEquals((C::make($data))->all(), (C::make($data))->only(null)->all());
        $this->assertEquals(['first' => 'Taylor'], (C::make($data))->only(['first', 'missing'])->all());
        $this->assertEquals(['first' => 'Taylor'], (C::make($data))->only('first', 'missing')->all());

        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'],
            (C::make($data))->only(['first', 'email'])->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'],
            (C::make($data))->only('first', 'email')->all());
    }

    public function testGettingAvgItemsFromCollection()
    {
        $c = C::make([(object)['foo' => 10], (object)['foo' => 20]]);
        $this->assertEquals(15, $c->avg(function ($item) {
            return $item->foo;
        }));
        $this->assertEquals(15, $c->avg('foo'));
        $this->assertEquals(15, $c->average('foo'));

        $c = C::make([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(15, $c->avg('foo'));

        $c = C::make([1, 2, 3, 4, 5]);
        $this->assertEquals(3, $c->avg());

        $c = C::make();
        $this->assertNull($c->avg());
    }

    public function testJsonSerialize()
    {
        $c = C::make([
            new TestJsonSerializeObject(),
            C::make(['foo' => 'bar']),
            'baz',
        ]);

        $this->assertSame([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            'baz',
        ], $c->jsonSerialize());
    }

    public function testCombineWithArray()
    {
        $expected = [
            1 => 4,
            2 => 5,
            3 => 6,
        ];

        $c = C::make(array_keys($expected));
        $actual = $c->combine(array_values($expected))->toArray();

        $this->assertSame($expected, $actual);
    }

    public function testCombineWithCollection()
    {
        $expected = [
            1 => 4,
            2 => 5,
            3 => 6,
        ];

        $keyCollection = C::make(array_keys($expected));
        $valueCollection = C::make(array_values($expected));
        $actual = $keyCollection->combine($valueCollection)->toArray();

        $this->assertSame($expected, $actual);
    }

    public function testReduce()
    {
        $data = C::make([1, 2, 3]);
        $this->assertEquals(6, $data->reduce(function ($carry, $element) {
            return $carry += $element;
        }));
    }

    public function testPipe()
    {
        $collection = C::make([1, 2, 3]);

        $this->assertEquals(6, $collection->pipe(function ($collection) {
            return $collection->sum();
        }));
    }

    public function testMedianValueWithArrayCollection()
    {
        $collection = C::make([1, 2, 2, 4]);

        $this->assertEquals(2, $collection->median());
    }

    public function testMedianValueByKey()
    {
        $collection = C::make([
            (object)['foo' => 1],
            (object)['foo' => 2],
            (object)['foo' => 2],
            (object)['foo' => 4],
        ]);
        $this->assertEquals(2, $collection->median('foo'));
    }

    public function testEvenMedianCollection()
    {
        $collection = C::make([
            (object)['foo' => 0],
            (object)['foo' => 3],
        ]);
        $this->assertEquals(1.5, $collection->median('foo'));
    }

    public function testMedianOutOfOrderCollection()
    {
        $collection = C::make([
            (object)['foo' => 0],
            (object)['foo' => 5],
            (object)['foo' => 3],
        ]);
        $this->assertEquals(3, $collection->median('foo'));
    }

    public function testMedianOnEmptyCollectionReturnsNull()
    {
        $collection = C::make();
        $this->assertNull($collection->median());
    }

    public function testModeOnNullCollection()
    {
        $collection = C::make();
        $this->assertNull($collection->mode());
    }

    public function testMode()
    {
        $collection = C::make([1, 2, 3, 4, 4, 5]);
        $this->assertEquals([4], $collection->mode());
    }

    public function testModeValueByKey()
    {
        $collection = C::make([
            (object)['foo' => 1],
            (object)['foo' => 1],
            (object)['foo' => 2],
            (object)['foo' => 4],
        ]);
        $this->assertEquals([1], $collection->mode('foo'));
    }

    public function testWithMultipleModeValues()
    {
        $collection = C::make([1, 2, 2, 1]);
        $this->assertEquals([1, 2], $collection->mode());
    }

    public function testSliceOffset()
    {
        $collection = C::make([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([4, 5, 6, 7, 8], $collection->slice(3)->values()->toArray());
    }

    public function testSliceNegativeOffset()
    {
        $collection = C::make([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([6, 7, 8], $collection->slice(-3)->values()->toArray());
    }

    public function testSliceOffsetAndLength()
    {
        $collection = C::make([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([4, 5, 6], $collection->slice(3, 3)->values()->toArray());
    }

    public function testSliceNegativeOffsetAndLength()
    {
        $collection = C::make([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([4, 5, 6], $collection->slice(-5, 3)->values()->toArray());
    }

    public function testCollectionFromTraversable()
    {
        $collection = C::make(new \ArrayObject([1, 2, 3]));
        $this->assertEquals([1, 2, 3], $collection->toArray());
    }

    public function testCollectionFromTraversableWithKeys()
    {
        $collection = C::make(new \ArrayObject(['foo' => 1, 'bar' => 2, 'baz' => 3]));
        $this->assertEquals(['foo' => 1, 'bar' => 2, 'baz' => 3], $collection->toArray());
    }

    public function testPartition()
    {
        $collection = C::make(range(1, 10));

        list($firstPartition, $secondPartition) = $collection->partition(function ($i) {
            return $i <= 5;
        });

        $this->assertEquals([1, 2, 3, 4, 5], $firstPartition->values()->toArray());
        $this->assertEquals([6, 7, 8, 9, 10], $secondPartition->values()->toArray());
    }

    public function testPartitionByKey()
    {
        $courses = C::make([
            ['free' => true, 'title' => 'Basic'],
            ['free' => false, 'title' => 'Premium'],
        ]);

        list($free, $premium) = $courses->partition('free');

        $this->assertSame([['free' => true, 'title' => 'Basic']], $free->values()->toArray());

        $this->assertSame([['free' => false, 'title' => 'Premium']], $premium->values()->toArray());
    }

    public function testPartitionPreservesKeys()
    {
        $courses = C::make([
            'a' => ['free' => true],
            'b' => ['free' => false],
            'c' => ['free' => true],
        ]);

        list($free, $premium) = $courses->partition('free');

        $this->assertSame(['a' => ['free' => true], 'c' => ['free' => true]], $free->toArray());

        $this->assertSame(['b' => ['free' => false]], $premium->toArray());
    }

    public function testPartitionEmptyCollection()
    {
        $collection = C::make();

        $this->assertCount(2, $collection->partition(function () {
            return true;
        }));
    }

    public function testTap()
    {
        $collection = C::make([1, 2, 3]);

        $fromTap = [];
        $collection = $collection->tap(function ($collection) use (&$fromTap) {
            $fromTap = $collection->slice(0, 1)->toArray();
        });

        $this->assertSame([1], $fromTap);
        $this->assertSame([1, 2, 3], $collection->toArray());
    }

    public function testPut()
    {
        $data = ['foo' => 'bar', 'baz' => 'dangle'];
        $this->assertSame(['foo' => 'baz', 'baz' => 'dangle'], C::make($data)->put('foo', 'baz')->all());
    }

    public function testWhen()
    {
        $collection = C::make(['michael', 'tom']);

        $collection->when(true, function (C $collection) {
            return $collection->push('adam');
        });

        $this->assertSame(['michael', 'tom', 'adam'], $collection->toArray());

        $collection = C::make(['michael', 'tom']);

        $collection->when(false, function ($collection) {
            return $collection->push('adam');
        });

        $this->assertSame(['michael', 'tom'], $collection->toArray());
    }
}

class TestSupportCollectionHigherOrderItem
{
    public $name = 'taylor';

    public function uppercase()
    {
        $this->name = strtoupper($this->name);
    }
}

class TestAccessorEloquentTestStub
{
    protected $attributes = [];

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    public function __get($attribute)
    {
        $accessor = 'get' . lcfirst($attribute) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor();
        }

        return $this->$attribute;
    }

    public function __isset($attribute)
    {
        $accessor = 'get' . lcfirst($attribute) . 'Attribute';

        if (method_exists($this, $accessor)) {
            return !is_null($this->$accessor());
        }

        return isset($this->$attribute);
    }

    public function getSomeAttribute()
    {
        return $this->attributes['some'];
    }
}

class TestArrayAccessImplementation implements ArrayAccess
{
    private $arr;

    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->arr[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->arr[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->arr[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->arr[$offset]);
    }
}

class TestJsonSerializeObject implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return ['foo' => 'bar'];
    }
}

date_default_timezone_set('UTC');
