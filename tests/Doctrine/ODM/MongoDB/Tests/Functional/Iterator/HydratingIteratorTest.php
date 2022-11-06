<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Iterator;

use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use Generator;
use MongoDB\BSON\ObjectId;

use function is_array;

final class HydratingIteratorTest extends BaseTest
{
    public function testConstructorRewinds(): void
    {
        $iterator = new HydratingIterator($this->getTraversable(), $this->uow, $this->dm->getClassMetadata(User::class));

        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->key());
        self::assertInstanceOf(User::class, $iterator->current());
    }

    public function testIteration(): void
    {
        $iterator = new HydratingIterator($this->getTraversable(), $this->uow, $this->dm->getClassMetadata(User::class));

        $expectedKey  = 0;
        $expectedHits = 1;

        foreach ($iterator as $key => $item) {
            self::assertSame($expectedKey++, $key);

            self::assertInstanceOf(User::class, $item);
            self::assertSame($expectedHits++, $item->getHits());
        }

        self::assertFalse($iterator->valid());
    }

    public function testIterationWithEmptySet(): void
    {
        $iterator = new HydratingIterator($this->getTraversable([]), $this->uow, $this->dm->getClassMetadata(User::class));

        $iterator->rewind();
        self::assertFalse($iterator->valid());
    }

    /**
     * @param array<string, mixed>|null $items
     *
     * @return Generator<array<string, mixed>>
     */
    private function getTraversable(?array $items = null): Generator
    {
        if (! is_array($items)) {
            $items = [
                ['_id' => new ObjectId(), 'username' => 'foo', 'hits' => 1],
                ['_id' => new ObjectId(), 'username' => 'bar', 'hits' => 2],
                ['_id' => new ObjectId(), 'username' => 'baz', 'hits' => 3],
            ];
        }

        foreach ($items as $item) {
            yield $item;
        }
    }
}
