<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Iterator;

use Doctrine\ODM\MongoDB\Iterator\PrimingIterator;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use MongoDB\BSON\ObjectId;
use function is_array;

final class PrimingIteratorTest extends BaseTest
{
    public function testPrimerIsCalledOnceForEveryField()
    {
        $primer = $this->createMock(ReferencePrimer::class);
        $class = $this->dm->getClassMetadata(User::class);
        $iterator = new PrimingIterator($this->getIterator(), $class, $primer, ['user' => true, 'hits' => true]);

        $primer
            ->expects($this->at(0))
            ->method('primeReferences')
            ->with($class, $iterator, 'user');
        $primer
            ->expects($this->at(1))
            ->method('primeReferences')
            ->with($class, $iterator, 'hits');

        $this->assertCount(3, $iterator->toArray());
        $this->assertCount(3, $iterator->toArray());
    }

    private function getIterator($items = null): \Iterator
    {
        if (! is_array($items)) {
            $items = [
                ['_id' => new ObjectId(), 'username' => 'foo', 'hits' => 1],
                ['_id' => new ObjectId(), 'username' => 'bar', 'hits' => 2],
                ['_id' => new ObjectId(), 'username' => 'baz', 'hits' => 3],
            ];
        }

        return new \ArrayIterator($items);
    }
}
