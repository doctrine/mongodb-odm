<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Iterator;

use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use MongoDB\BSON\ObjectId;
use function is_array;

final class HydratingIteratorTest extends BaseTest
{
    public function testConstructorRewinds()
    {
        $iterator = new HydratingIterator($this->getTraversable(), $this->uow, $this->dm->getClassMetadata(User::class));

        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->key());
        $this->assertInstanceOf(User::class, $iterator->current());
    }

    public function testIteration()
    {
        $iterator = new HydratingIterator($this->getTraversable(), $this->uow, $this->dm->getClassMetadata(User::class));

        $expectedKey = 0;
        $expectedHits = 1;

        foreach ($iterator as $key => $item) {
            $this->assertSame($expectedKey++, $key);

            $this->assertInstanceOf(User::class, $item);
            $this->assertSame($expectedHits++, $item->getHits());
        }

        $this->assertFalse($iterator->valid());
    }

    public function testIterationWithEmptySet()
    {
        $iterator = new HydratingIterator($this->getTraversable([]), $this->uow, $this->dm->getClassMetadata(User::class));

        $iterator->rewind();
        $this->assertFalse($iterator->valid());
    }

    private function getTraversable($items = null)
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
