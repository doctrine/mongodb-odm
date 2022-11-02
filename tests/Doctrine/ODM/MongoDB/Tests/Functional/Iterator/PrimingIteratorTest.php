<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Iterator;

use ArrayIterator;
use Closure;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\PrimingIterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\ReferencePrimer;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Profile;
use Documents\ProfileNotify;
use Documents\User;
use Iterator;
use MongoDB\BSON\ObjectId;

final class PrimingIteratorTest extends BaseTest
{
    /** @var class-string[] */
    private array $callbackCalls = [];

    public function testPrimerIsCalledOnceForEveryField(): void
    {
        $primer   = new ReferencePrimer($this->dm, $this->uow);
        $class    = $this->dm->getClassMetadata(User::class);
        $iterator = new PrimingIterator($this->getIterator(), $class, $primer, [
            'profile' => $this->createPrimerCallback(),
            'profileNotify' => $this->createPrimerCallback(),
        ]);

        self::assertCount(3, $iterator->toArray());
        self::assertCount(3, $iterator->toArray());

        self::assertSame([Profile::class, ProfileNotify::class], $this->callbackCalls);
    }

    private function createPrimerCallback(): Closure
    {
        return function (DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) {
            $this->callbackCalls[] = $class->name;
        };
    }

    private function getIterator(): Iterator
    {
        $items = [
            $this->createUserForPriming(),
            $this->createUserForPriming(),
            $this->createUserForPriming(),
        ];

        return new ArrayIterator($items);
    }

    private function createUserForPriming(): User
    {
        $user = new User();
        $user->setId(new ObjectId());
        $user->setProfile($this->dm->getReference(Profile::class, new ObjectId()));
        $user->setProfileNotify($this->dm->getReference(ProfileNotify::class, new ObjectId()));

        return $user;
    }
}
