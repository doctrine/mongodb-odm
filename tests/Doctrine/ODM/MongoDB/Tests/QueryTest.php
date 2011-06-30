<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\QueryBuilder;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class QueryTest extends BaseTest
{
    public function testThatOrAcceptsAnotherQuery()
    {
        $kris = new Person('Kris');
        $chris = new Person('Chris');
        $this->dm->persist($kris);
        $this->dm->persist($chris);
        $this->dm->flush();

        $class = __NAMESPACE__.'\Person';
        $expression1 = array('firstName' => 'Kris');
        $expression2 = array('firstName' => 'Chris');

        $qb = $this->dm->createQueryBuilder($class);
        $qb->addOr($qb->expr()->field('firstName')->equals('Kris'));
        $qb->addOr($qb->expr()->field('firstName')->equals('Chris'));

        $this->assertEquals(array('$or' => array(
            array('firstName' => 'Kris'),
            array('firstName' => 'Chris')
        )), $qb->getQueryArray());

        $query = $qb->getQuery();
        $users = $query->execute();

        $this->assertInstanceOf('Doctrine\MongoDB\Cursor', $users);
        $this->assertEquals(2, count($users));
    }

    public function testReferences()
    {
        $kris = new Person('Kris');
        $jon = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $kris->bestFriend = $jon;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person');
        $qb->field('bestFriend')->references($jon);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals(array(
            'bestFriend.$ref' => 'people',
            'bestFriend.$id' => new \MongoId($jon->id),
            'bestFriend.$db' => 'doctrine_odm_tests',
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertEquals(1, $query->count());
        $this->assertSame($kris, $query->getSingleResult());
    }

    public function testIncludesReferenceTo()
    {
        $kris = new Person('Kris');
        $jon = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $jon->friends[] = $kris;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person');
        $qb->field('friends')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals(array(
            'friends' => array(
                '$elemMatch' => array(
                    '$ref' => 'people',
                    '$id' => new \MongoId($kris->id),
                    '$db' => 'doctrine_odm_tests',
                ),
            ),
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertEquals(1, $query->count());
        $this->assertSame($jon, $query->getSingleResult());
    }

    public function testQueryIdIn()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $this->dm->persist($user);
        $this->dm->flush();

        $mongoId = new \MongoId($user->getId());
        $ids = array($mongoId);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('_id')->in($ids);
        $query = $qb->getQuery();
        $results = $query->toArray();
        $this->assertEquals(1, count($results));
    }

    public function testEmbeddedSet()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->insert()
            ->field('testInt')->set('0')
            ->field('intfields.intone')->set('1')
            ->field('intfields.inttwo')->set('2');
        $this->assertEquals(array('testInt' => 0, 'intfields' => array('intone' => 1, 'inttwo' => 2)), $qb->getNewObj());
    }
}

/** @ODM\Document(collection="people") */
class Person
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $firstName;

    /** @ODM\ReferenceOne */
    public $bestFriend;

    /** @ODM\ReferenceMany */
    public $friends = array();

    public function __construct($firstName)
    {
        $this->firstName = $firstName;
    }
}