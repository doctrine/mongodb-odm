<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\SimpleReferenceUser;
use Documents\User;

class SimpleReferencesTest extends BaseTest
{
    private $user;
    private $test;

    public function setUp()
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
        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes('Documents\SimpleReferenceUser');
        $this->assertEquals(['userId' => 1], $indexes[0]['keys']);
    }

    public function testStorage()
    {
        $test = $this->dm->getDocumentCollection('Documents\SimpleReferenceUser')->findOne();
        $this->assertNotNull($test);
        $this->assertInstanceOf('MongoId', $test['userId']);
        $this->assertInstanceOf('MongoId', $test['users'][0]);
    }

    public function testQuery()
    {
        $this->user = $this->dm->getRepository('Documents\User')->findOneBy(['username' => 'jwage']);

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->references($this->user);
        $this->assertEquals(['userId' => new \MongoId($this->user->getId())], $qb->getQuery()->debug('query'));

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->equals($this->user->getId());
        $this->assertEquals(['userId' => new \MongoId($this->user->getId())], $qb->getQuery()->debug('query'));

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->in([$this->user->getId()]);
        $this->assertEquals(['userId' => ['$in' => [new \MongoId($this->user->getId())]]], $qb->getQuery()->debug('query'));
    }

    public function testProxy()
    {
        $this->user = $this->dm->getRepository('Documents\User')->findOneBy(['username' => 'jwage']);

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->references($this->user);
        $this->assertEquals(['userId' => new \MongoId($this->user->getId())], $qb->getQuery()->debug('query'));

        $this->dm->clear();

        $test = $qb->getQuery()->getSingleResult();

        $this->assertNotNull($test);
        $this->assertNotNull($test->getUser());
        $this->assertInstanceOf('Proxies\__CG__\Documents\User', $test->getUser());
        $this->assertFalse($test->getUser()->__isInitialized__);
        $this->assertEquals('jwage', $test->getUser()->getUsername());
        $this->assertTrue($test->getUser()->__isInitialized__);
    }

    public function testPersistentCollectionOwningSide()
    {
        $test = $this->dm->getRepository('Documents\SimpleReferenceUser')->findOneBy([]);
        $users = $test->getUsers()->toArray();
        $this->assertEquals(2, $test->getUsers()->count());
        $this->assertEquals('jwage', current($users)->getUsername());
        $this->assertEquals('jwage', end($users)->getUsername());
    }

    public function testPersistentCollectionInverseSide()
    {
        $user = $this->dm->getRepository('Documents\User')->findOneBy([]);
        $test = $user->getSimpleReferenceManyInverse()->toArray();
        $this->assertEquals('test' ,current($test)->getName());
    }

    public function testOneInverseSide()
    {
        $user = $this->dm->getRepository('Documents\User')->findOneBy([]);
        $test = $user->getSimpleReferenceOneInverse();
        $this->assertEquals('test', $test->getName());
    }

    public function testQueryForNonIds() {
        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->equals(null);
        $this->assertEquals(['userId' => null], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->notEqual(null);
        $this->assertEquals(['userId' => ['$ne' => null]], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->exists(true);
        $this->assertEquals(['userId' => ['$exists' => true]], $qb->getQueryArray());
    }

    public function testRemoveDocumentByEmptyRefMany()
    {
        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('users')->equals([]);
        $this->assertEquals(['users' => []], $qb->getQueryArray());

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('users')->equals(new \stdClass());
        $this->assertEquals(['users' => new \stdClass()], $qb->getQueryArray());
    }
}
