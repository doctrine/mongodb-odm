<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\SimpleReferenceUser;
use Documents\User;
use MongoDB\BSON\ObjectId;
use stdClass;

use function assert;
use function current;
use function end;

class SimpleReferencesTest extends BaseTestCase
{
    private User $user;

    private SimpleReferenceUser $test;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('jwage');
        $this->test = new SimpleReferenceUser();
        $this->test->setName('test');
        $this->test->setUser($this->user);
        $this->test->addUser($this->user);
        $this->test->addUser($this->user);
        $this->dm->persist($this->test);
        $this->dm->persist($this->user);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testIndexes(): void
    {
        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes(SimpleReferenceUser::class);
        self::assertEquals(['userId' => 1], $indexes[0]['keys']);
    }

    public function testStorage(): void
    {
        $test = $this->dm->getDocumentCollection(SimpleReferenceUser::class)->findOne();
        self::assertNotNull($test);
        self::assertInstanceOf(ObjectId::class, $test['userId']);
        self::assertInstanceOf(ObjectId::class, $test['users'][0]);
    }

    public function testQuery(): void
    {
        $this->user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jwage']);

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->references($this->user);
        self::assertEquals(['userId' => new ObjectId($this->user->getId())], $qb->getQuery()->debug('query'));

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->equals($this->user->getId());
        self::assertEquals(['userId' => new ObjectId($this->user->getId())], $qb->getQuery()->debug('query'));

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->in([$this->user->getId()]);
        self::assertEquals(['userId' => ['$in' => [new ObjectId($this->user->getId())]]], $qb->getQuery()->debug('query'));
    }

    public function testProxy(): void
    {
        $this->user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jwage']);

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->references($this->user);
        self::assertEquals(['userId' => new ObjectId($this->user->getId())], $qb->getQuery()->debug('query'));

        $this->dm->clear();

        $test = $qb->getQuery()->getSingleResult();
        assert($test instanceof SimpleReferenceUser);

        self::assertNotNull($test);
        $user = $test->getUser();
        self::assertNotNull($user);
        self::assertInstanceOf(User::class, $user);
        self::assertTrue(self::isLazyObject($user));
        self::assertTrue($this->uow->isUninitializedObject($user));
        self::assertEquals('jwage', $user->getUsername());
        self::assertFalse($this->uow->isUninitializedObject($user));
    }

    public function testPersistentCollectionOwningSide(): void
    {
        $test  = $this->dm->getRepository(SimpleReferenceUser::class)->findOneBy([]);
        $users = $test->getUsers()->toArray();
        self::assertEquals(2, $test->getUsers()->count());
        self::assertEquals('jwage', current($users)->getUsername());
        self::assertEquals('jwage', end($users)->getUsername());
    }

    public function testPersistentCollectionInverseSide(): void
    {
        $user = $this->dm->getRepository(User::class)->findOneBy([]);
        $test = $user->getSimpleReferenceManyInverse()->toArray();
        self::assertEquals('test', current($test)->getName());
    }

    public function testOneInverseSide(): void
    {
        $user = $this->dm->getRepository(User::class)->findOneBy([]);
        $test = $user->getSimpleReferenceOneInverse();
        self::assertEquals('test', $test->getName());
    }

    public function testQueryForNonIds(): void
    {
        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->equals(null);
        self::assertEquals(['userId' => null], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->notEqual(null);
        self::assertEquals(['userId' => ['$ne' => null]], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->exists(true);
        self::assertEquals(['userId' => ['$exists' => true]], $qb->getQueryArray());
    }

    public function testRemoveDocumentByEmptyRefMany(): void
    {
        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('users')->equals([]);
        self::assertEquals(['users' => []], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('users')->equals(new stdClass());
        self::assertEquals(['users' => new stdClass()], $qb->getQueryArray());
    }
}
