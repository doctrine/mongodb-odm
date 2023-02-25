<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsGroup;
use Documents\CmsUser;

use function get_class;

class PersistentCollectionCloneTest extends BaseTestCase
{
    private ?CmsUser $user1 = null;

    private ?CmsUser $user2 = null;

    public function setUp(): void
    {
        parent::setUp();

        $user1           = new CmsUser();
        $user1->username = 'beberlei';
        $user1->name     = 'Benjamin';
        $user1->status   = 'active';
        $group1          = new CmsGroup();
        $group1->name    = 'test';
        $group2          = new CmsGroup();
        $group2->name    = 'test';
        $user1->addGroup($group1);
        $user1->addGroup($group2);
        $user2           = new CmsUser();
        $user2->username = 'romanb';
        $user2->name     = 'Roman';
        $user2->status   = 'active';

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $this->dm->clear();

        $this->user1 = $this->dm->find(get_class($user1), $user1->id);
        $this->user2 = $this->dm->find(get_class($user1), $user2->id);
    }

    public function testClonePersistentCollectionAndReuse(): void
    {
        $user1 = $this->user1;

        $user1->groups = clone $user1->groups;

        $this->dm->flush();
        $this->dm->clear();

        $user1 = $this->dm->find(get_class($user1), $user1->id);

        self::assertCount(2, $user1->groups);
    }

    public function testClonePersistentCollectionAndShare(): void
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $user2->groups = clone $user1->groups;

        $this->dm->flush();
        $this->dm->clear();

        $user1 = $this->dm->find(get_class($user1), $user1->id);
        $user2 = $this->dm->find(get_class($user1), $user2->id);

        self::assertCount(2, $user1->groups);
        self::assertCount(2, $user2->groups);
    }

    public function testCloneThenDirtyPersistentCollection(): void
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $group3        = new CmsGroup();
        $group3->name  = 'test';
        $user2->groups = clone $user1->groups;
        $user2->groups->add($group3);

        $this->dm->persist($group3);
        $this->dm->flush();
        $this->dm->clear();

        $user1 = $this->dm->find(get_class($user1), $user1->id);
        $user2 = $this->dm->find(get_class($user1), $user2->id);

        self::assertCount(3, $user2->groups);
        self::assertCount(2, $user1->groups);
    }

    public function testNotCloneAndPassAroundFlush(): void
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $group3        = new CmsGroup();
        $group3->name  = 'test';
        $user2->groups = $user1->groups;
        $user2->groups->add($group3);

        self::assertInstanceOf(PersistentCollectionInterface::class, $user1->groups);
        self::assertCount(1, $user1->groups->getInsertDiff());

        $this->dm->persist($group3);
        $this->dm->flush();
        $this->dm->clear();

        $user1 = $this->dm->find(get_class($user1), $user1->id);
        $user2 = $this->dm->find(get_class($user1), $user2->id);

        self::assertCount(3, $user2->groups);
        self::assertCount(3, $user1->groups);
    }
}
