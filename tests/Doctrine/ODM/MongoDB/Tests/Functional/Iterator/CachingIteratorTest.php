<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Iterator;

use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Exception;
use Generator;
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

    private function getTraversable(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }

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
