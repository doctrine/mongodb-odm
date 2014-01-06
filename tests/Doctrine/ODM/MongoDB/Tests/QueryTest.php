<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\QueryBuilder;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class QueryTest extends BaseTest
{
    public function testSelectAndSelectSliceOnSameField()
    {
        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person')
            ->exclude('comments')
            ->select('comments')
            ->selectSlice('comments', 0, 10);
        $query = $qb->getQuery();
        $results = $query->execute();
    }

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
            'bestFriend.$db' => DOCTRINE_MONGODB_DATABASE,
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
                    '$db' => DOCTRINE_MONGODB_DATABASE,
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

    public function testElemMatch()
    {
        $refId = '000000000000000000000001';

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $embeddedQb = $this->dm->createQueryBuilder('Documents\Phonenumber');

        $qb->field('phonenumbers')->elemMatch($embeddedQb->expr()
            ->field('lastCalledBy.id')->equals($refId)
        );
        $query = $qb->getQuery();

        $expectedQuery = array('phonenumbers' => array('$elemMatch' => array('lastCalledBy.$id' => new \MongoId($refId))));
        $this->assertEquals($expectedQuery, $query->debug('query'));
    }

    public function testQueryWithMultipleEmbeddedDocuments()
    {
        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\EmbedTest')
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.name')->equals('Foo');
        $query = $qb->getQuery();
        $this->assertEquals(array('eO.eO.e1.eO.n' => 'Foo'), $query->debug('query'));
    }

    public function testQueryWithMultipleEmbeddedDocumentsAndReference()
    {
        $mongoId = new \MongoId();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\EmbedTest')
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.pet.owner.id')->equals((string) $mongoId);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertTrue(array_key_exists('eO.eO.e1.eO.eP.pO._id', $debug));
        $this->assertEquals($mongoId, $debug['eO.eO.e1.eO.eP.pO._id']);
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

/** @ODM\EmbeddedDocument */
class Pet
{
    /** @ODM\ReferenceOne(name="pO", targetDocument="Doctrine\ODM\MongoDB\Tests\Person") */
    public $owner;
}

/** @ODM\EmbeddedDocument */
class EmbedTest
{
    /** @ODM\EmbedOne(name="eO", targetDocument="Doctrine\ODM\MongoDB\Tests\EmbedTest") */
    public $embeddedOne;

    /** @ODM\EmbedMany(name="e1", targetDocument="Doctrine\ODM\MongoDB\Tests\EmbedTest") */
    public $embeddedMany;

    /** @ODM\String(name="n") */
    public $name;

    /** @ODM\ReferenceOne(name="p", targetDocument="Doctrine\ODM\MongoDB\Tests\Person") */
    public $person;

    /** @ODM\EmbedOne(name="eP", targetDocument="Doctrine\ODM\MongoDB\Tests\Pet") */
    public $pet;
}