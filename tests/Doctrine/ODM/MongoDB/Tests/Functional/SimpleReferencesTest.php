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
        $this->dm->flush(null, array('safe' => true));
        $this->dm->clear();
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
        $this->user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jwage'));

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->references($this->user);
        $this->assertEquals(array('userId' => new \MongoId($this->user->getId())), $qb->getQuery()->debug());

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->equals($this->user->getId());
        $this->assertEquals(array('userId' => new \MongoId($this->user->getId())), $qb->getQuery()->debug());
    }

    public function testProxy()
    {
        $this->user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jwage'));

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser');
        $qb->field('user')->references($this->user);
        $this->assertEquals(array('userId' => new \MongoId($this->user->getId())), $qb->getQuery()->debug());

        $this->dm->clear();

        $test = $qb->getQuery()->getSingleResult();

        $this->assertNotNull($test);
        $this->assertNotNull($test->getUser());
        $this->assertInstanceOf('Proxies\DocumentsUserProxy', $test->getUser());
        $this->assertFalse($test->getUser()->__isInitialized__);
        $this->assertEquals('jwage', $test->getUser()->getUsername());
        $this->assertTrue($test->getUser()->__isInitialized__);
    }

    public function testPersistentCollectionOwningSide()
    {
        $test = $this->dm->getRepository('Documents\SimpleReferenceUser')->findOneBy(array());
        $users = $test->getUsers()->toArray();
        $this->assertEquals(2, $test->getUsers()->count());
        $this->assertEquals('jwage', current($users)->getUsername());
        $this->assertEquals('jwage', end($users)->getUsername());
    }

    public function testPersistentCollectionInverseSide()
    {
        $user = $this->dm->getRepository('Documents\User')->findOneBy(array());
        $test = $user->getSimpleReferenceManyInverse()->toArray();
        $this->assertEquals('test' ,current($test)->getName());
    }

    public function testOneInverseSide()
    {
        $user = $this->dm->getRepository('Documents\User')->findOneBy(array());
        $test = $user->getSimpleReferenceOneInverse();
        $this->assertEquals('test', $test->getName());
    }
}