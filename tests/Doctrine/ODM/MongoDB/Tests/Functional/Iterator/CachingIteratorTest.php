<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Iterator;

use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Exception;
use Generator;
use Iterator;
use PHPUnit\Framework\TestCase;
use Throwable;

use function iterator_to_array;

class CachingIteratorTest extends TestCase
{
    /**
     * Sanity check for all following tests.
     */
    public function testTraversingGeneratorConsumesIt(): void
    {
        $iterator = $this->getTraversable([1, 2, 3]);
        self::assertSame([1, 2, 3], iterator_to_array($iterator));
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Cannot traverse an already closed generator');
        self::assertSame([1, 2, 3], iterator_to_array($iterator));
    }

    public function testConstructorRewinds(): void
    {
        $iterator = new CachingIterator($this->getTraversable([1, 2, 3]));

        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->key());
        self::assertSame(1, $iterator->current());
    }

    public function testIteration(): void
    {
        $iterator = new CachingIterator($this->getTraversable([1, 2, 3]));

        $expectedKey  = 0;
        $expectedItem = 1;

        foreach ($iterator as $key => $item) {
            self::assertSame($expectedKey++, $key);
            self::assertSame($expectedItem++, $item);
        }

        self::assertFalse($iterator->valid());
    }

    public function testIterationWithEmptySet(): void
    {
        $iterator = new CachingIterator($this->getTraversable([]));

        $iterator->rewind();
        self::assertFalse($iterator->valid());
    }

    public function testIterationWithInvalidIterator(): void
    {
        $mock = $this->createMock(Iterator::class);
        // The method next() should not be called on a dead cursor.
        $mock->expects(self::never())->method('next');
        // The method valid() return false on a dead cursor.
        $mock->expects(self::once())->method('valid')->willReturn(false);

        $iterator = new CachingIterator($mock);

        $this->assertEquals([], $iterator->toArray());
    }

    public function testPartialIterationDoesNotExhaust(): void
    {
        $traversable = $this->getTraversableThatThrows([1, 2, new Exception()]);
        $iterator    = new CachingIterator($traversable);

        $expectedKey  = 0;
        $expectedItem = 1;

        foreach ($iterator as $key => $item) {
            self::assertSame($expectedKey++, $key);
            self::assertSame($expectedItem++, $item);

            if ($key === 1) {
                break;
            }
        }

        self::assertTrue($iterator->valid());
    }

    public function testRewindAfterPartialIteration(): void
    {
        $iterator = new CachingIterator($this->getTraversable([1, 2, 3]));

        $iterator->rewind();
        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->key());
        self::assertSame(1, $iterator->current());

        $iterator->next();
        self::assertSame([1, 2, 3], iterator_to_array($iterator));
    }

    public function testToArray(): void
    {
        $iterator = new CachingIterator($this->getTraversable([1, 2, 3]));
        self::assertSame([1, 2, 3], $iterator->toArray());
    }

    public function testToArrayAfterPartialIteration(): void
    {
        $iterator = new CachingIterator($this->getTraversable([1, 2, 3]));

        $iterator->rewind();
        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->key());
        self::assertSame(1, $iterator->current());

        $iterator->next();
        self::assertSame([1, 2, 3], $iterator->toArray());
    }

    public function testCount(): void
    {
        $iterator = new CachingIterator($this->getTraversable([1, 2, 3]));
        self::assertCount(3, $iterator);
    }

    public function testCountAfterPartialIteration(): void
    {
        $iterator = new CachingIterator($this->getTraversable([1, 2, 3]));

        $iterator->rewind();
        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->key());
        self::assertSame(1, $iterator->current());

        $iterator->next();
        self::assertSame(1, $iterator->key());
        self::assertSame(2, $iterator->current());

        self::assertCount(3, $iterator);
        self::assertTrue($iterator->valid());
        self::assertSame(1, $iterator->key());
        self::assertSame(2, $iterator->current());
    }

    public function testCountWithEmptySet(): void
    {
        $iterator = new CachingIterator($this->getTraversable([]));
        self::assertCount(0, $iterator);
    }

    /**
     * This protects against iterators that return valid keys on invalid
     * positions, which was the case in ext-mongodb until PHPC-1748 was fixed.
     */
    public function testWithWrongIterator(): void
    {
        $nestedIterator = new class implements Iterator {
            private int $i = 0;

            public function current(): int
            {
                return $this->i;
            }

            public function next(): void
            {
                $this->i++;
            }

            public function key(): int
            {
                return $this->i;
            }

            public function valid(): bool
            {
                return $this->i === 0;
            }

            public function rewind(): void
            {
                $this->i = 0;
            }
        };

        $iterator = new CachingIterator($nestedIterator);
        self::assertCount(1, $iterator);
    }

    /**
     * @param T[] $items
     *
     * @return Generator<T>
     *
     * @template T
     */
    private function getTraversable(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }

    /**
     * @param array<mixed|Exception> $items
     *
     * @return Generator<mixed>
     */
    private function getTraversableThatThrows(array $items): Generator
    {
        foreach ($items as $item) {
            if ($item instanceof Exception) {
                throw $item;
            }

            yield $item;
        }
    }
}
