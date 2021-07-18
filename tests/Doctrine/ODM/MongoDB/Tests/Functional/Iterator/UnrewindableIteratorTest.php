<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Iterator;

use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Exception;
use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Throwable;

use function iterator_to_array;

class UnrewindableIteratorTest extends TestCase
{
    /**
     * Sanity check for all following tests.
     */
    public function testTraversingGeneratorConsumesIt(): void
    {
        $iterator = $this->getTraversable([1, 2, 3]);
        $this->assertSame([1, 2, 3], iterator_to_array($iterator));
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Cannot traverse an already closed generator');
        $this->assertSame([1, 2, 3], iterator_to_array($iterator));
    }

    public function testConstructorRewinds(): void
    {
        $iterator = new UnrewindableIterator($this->getTraversable([1, 2, 3]));

        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->key());
        $this->assertSame(1, $iterator->current());
    }

    public function testIteration(): void
    {
        $iterator = new UnrewindableIterator($this->getTraversable([1, 2, 3]));

        $expectedKey  = 0;
        $expectedItem = 1;

        foreach ($iterator as $key => $item) {
            $this->assertSame($expectedKey++, $key);
            $this->assertSame($expectedItem++, $item);
        }

        $this->assertFalse($iterator->valid());
    }

    public function testIterationWithEmptySet(): void
    {
        $iterator = new UnrewindableIterator($this->getTraversable([]));

        $iterator->rewind();
        $this->assertFalse($iterator->valid());
    }

    public function testToArrayWithEmptySet(): void
    {
        $iterator = new UnrewindableIterator($this->getTraversable([]));

        $this->assertEquals([], $iterator->toArray());
    }

    public function testPartialIterationDoesNotExhaust(): void
    {
        $traversable = $this->getTraversableThatThrows([1, 2, new Exception()]);
        $iterator    = new UnrewindableIterator($traversable);

        $expectedKey  = 0;
        $expectedItem = 1;

        foreach ($iterator as $key => $item) {
            $this->assertSame($expectedKey++, $key);
            $this->assertSame($expectedItem++, $item);

            if ($key === 1) {
                break;
            }
        }

        $this->assertTrue($iterator->valid());
    }

    public function testRewindAfterPartialIteration(): void
    {
        $iterator = new UnrewindableIterator($this->getTraversable([1, 2, 3]));

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->key());
        $this->assertSame(1, $iterator->current());

        $iterator->next();
        $this->expectException(LogicException::class);
        iterator_to_array($iterator);
    }

    public function testToArray(): void
    {
        $iterator = new UnrewindableIterator($this->getTraversable([1, 2, 3]));
        $this->assertSame([1, 2, 3], $iterator->toArray());
    }

    public function testToArrayAfterPartialIteration(): void
    {
        $iterator = new UnrewindableIterator($this->getTraversable([1, 2, 3]));

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->key());
        $this->assertSame(1, $iterator->current());

        $iterator->next();
        $this->expectException(LogicException::class);
        $iterator->toArray();
    }

    private function getTraversable($items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }

    private function getTraversableThatThrows($items): Generator
    {
        foreach ($items as $item) {
            if ($item instanceof Exception) {
                throw $item;
            }

            yield $item;
        }
    }
}
