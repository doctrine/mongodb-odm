<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\QueryBuilder;

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
            'bestFriend._doctrine_class_name' => 'Doctrine\ODM\MongoDB\Tests\Person',
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
                    '_doctrine_class_name' => 'Doctrine\ODM\MongoDB\Tests\Person',
                ),
            ),
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertEquals(1, $query->count());
        $this->assertSame($jon, $query->getSingleResult());
    }
}

/** @Document(collection="people") */
class Person
{
    /** @Id */
    public $id;

    /** @String */
    public $firstName;

    /** @ReferenceOne */
    public $bestFriend;

    /** @ReferenceMany */
    public $friends = array();

    public function __construct($firstName)
    {
        $this->firstName = $firstName;
    }
}