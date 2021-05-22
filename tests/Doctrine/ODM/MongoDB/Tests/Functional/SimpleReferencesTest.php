<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\SimpleReferenceUser;
use Documents\User;
use MongoDB\BSON\ObjectId;
use ProxyManager\Proxy\GhostObjectInterface;
use stdClass;

use function assert;
use function current;
use function end;

class SimpleReferencesTest extends BaseTest
{
    /** @var User */
    private $user;

    /** @var SimpleReferenceUser */
    private $test;

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

    public function testIndexes()
    {
        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes(SimpleReferenceUser::class);
        $this->assertEquals(['userId' => 1], $indexes[0]['keys']);
    }

    public function testStorage()
    {
        $test = $this->dm->getDocumentCollection(SimpleReferenceUser::class)->findOne();
        $this->assertNotNull($test);
        $this->assertInstanceOf(ObjectId::class, $test['userId']);
        $this->assertInstanceOf(ObjectId::class, $test['users'][0]);
    }

    public function testQuery()
    {
        $this->user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jwage']);

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->references($this->user);
        $this->assertEquals(['userId' => new ObjectId($this->user->getId())], $qb->getQuery()->debug('query'));

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->equals($this->user->getId());
        $this->assertEquals(['userId' => new ObjectId($this->user->getId())], $qb->getQuery()->debug('query'));

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->in([$this->user->getId()]);
        $this->assertEquals(['userId' => ['$in' => [new ObjectId($this->user->getId())]]], $qb->getQuery()->debug('query'));
    }

    public function testProxy()
    {
        $this->user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jwage']);

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->references($this->user);
        $this->assertEquals(['userId' => new ObjectId($this->user->getId())], $qb->getQuery()->debug('query'));

        $this->dm->clear();

        $test = $qb->getQuery()->getSingleResult();
        assert($test instanceof SimpleReferenceUser);

        $this->assertNotNull($test);
        $user = $test->getUser();
        assert($user instanceof User && $user instanceof GhostObjectInterface);
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(GhostObjectInterface::class, $user);
        $this->assertFalse($user->isProxyInitialized());
        $this->assertEquals('jwage', $user->getUsername());
        $this->assertTrue($user->isProxyInitialized());
    }

    public function testPersistentCollectionOwningSide()
    {
        $test  = $this->dm->getRepository(SimpleReferenceUser::class)->findOneBy([]);
        $users = $test->getUsers()->toArray();
        $this->assertEquals(2, $test->getUsers()->count());
        $this->assertEquals('jwage', current($users)->getUsername());
        $this->assertEquals('jwage', end($users)->getUsername());
    }

    public function testPersistentCollectionInverseSide()
    {
        $user = $this->dm->getRepository(User::class)->findOneBy([]);
        $test = $user->getSimpleReferenceManyInverse()->toArray();
        $this->assertEquals('test', current($test)->getName());
    }

    public function testOneInverseSide()
    {
        $user = $this->dm->getRepository(User::class)->findOneBy([]);
        $test = $user->getSimpleReferenceOneInverse();
        $this->assertEquals('test', $test->getName());
    }

    public function testQueryForNonIds()
    {
        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->equals(null);
        $this->assertEquals(['userId' => null], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->notEqual(null);
        $this->assertEquals(['userId' => ['$ne' => null]], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('user')->exists(true);
        $this->assertEquals(['userId' => ['$exists' => true]], $qb->getQueryArray());
    }

    public function testRemoveDocumentByEmptyRefMany()
    {
        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('users')->equals([]);
        $this->assertEquals(['users' => []], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class);
        $qb->field('users')->equals(new stdClass());
        $this->assertEquals(['users' => new stdClass()], $qb->getQueryArray());
    }
}
