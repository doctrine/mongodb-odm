<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\User;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\ReadPreference;
use MongoDB\Model\CachingIterator;

class QueryTest extends BaseTest
{
    public function testSelectAndSelectSliceOnSameField()
    {
        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person')
            ->exclude('comments')
            ->select('comments')
            ->selectSlice('comments', 0, 10);

        $query = $qb->getQuery();

        $this->assertEquals(['comments' => ['$slice' => [0, 10]]], $query->getQuery()['select']);

        $query = $qb->getQuery();
        $query->execute();
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

        $this->assertInstanceOf(Cursor::class, $users);
        $this->assertCount(2, $users->toArray());
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
            'bestFriend.$id' => new \MongoDB\BSON\ObjectId($jon->id),
            'bestFriend.$db' => DOCTRINE_MONGODB_DATABASE,
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertCount(1, $query->toArray());
        $this->assertSame($kris, $query->getSingleResult());
    }

    public function testReferencesStoreAsId()
    {
        $kris = new Person('Kris');
        $jon = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $kris->bestFriendSimple = $jon;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person');
        $qb->field('bestFriendSimple')->references($jon);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals(array(
            'bestFriendSimple' => new \MongoDB\BSON\ObjectId($jon->id),
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertCount(1, $query->toArray());
        $this->assertSame($kris, $query->getSingleResult());
    }

    public function testReferencesStoreAsDbRef()
    {
        $kris = new Person('Kris');
        $jon = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $kris->bestFriendPartial = $jon;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person');
        $qb->field('bestFriendPartial')->references($jon);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals(array(
            'bestFriendPartial.$ref' => 'people',
            'bestFriendPartial.$id' => new \MongoDB\BSON\ObjectId($jon->id),
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertCount(1, $query->toArray());
        $this->assertSame($kris, $query->getSingleResult());
    }

    public function testIncludesReferenceToWithStoreAsDbRefWithDb()
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
                    '$id' => new \MongoDB\BSON\ObjectId($kris->id),
                    '$db' => DOCTRINE_MONGODB_DATABASE,
                ),
            ),
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertCount(1, $query->toArray());
        $this->assertSame($jon, $query->getSingleResult());
    }

    public function testIncludesReferenceToWithStoreAsId()
    {
        $kris = new Person('Kris');
        $jon = new Person('Jon');
        $jachim = new Person('Jachim');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->persist($jachim);
        $this->dm->flush();

        $jon->friendsSimple[] = $kris;
        $jon->friendsSimple[] = $jachim;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person');
        $qb->field('friendsSimple')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals(array(
            'friendsSimple' =>  new \MongoDB\BSON\ObjectId($kris->id)
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertCount(1, $query->toArray());
        $this->assertSame($jon, $query->getSingleResult());
    }

    public function testIncludesReferenceToWithStoreAsDbRef()
    {
        $kris = new Person('Kris');
        $jon = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $jon->friendsPartial[] = $kris;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\Person');
        $qb->field('friendsPartial')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals(array(
            'friendsPartial' => array(
                '$elemMatch' => array(
                    '$ref' => 'people',
                    '$id' => new \MongoDB\BSON\ObjectId($kris->id)
                ),
            ),
        ), $queryArray);

        $query = $qb->getQuery();

        $this->assertCount(1, $query->toArray());
        $this->assertSame($jon, $query->getSingleResult());
    }

    public function testQueryIdIn()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $this->dm->persist($user);
        $this->dm->flush();

        $identifier = new \MongoDB\BSON\ObjectId($user->getId());
        $ids = array($identifier);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('_id')->in($ids);
        $query = $qb->getQuery();
        $results = $query->toArray();
        $this->assertCount(1, $results);
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

        $expectedQuery = array('phonenumbers' => array('$elemMatch' => array('lastCalledBy.$id' => new \MongoDB\BSON\ObjectId($refId))));
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
        $identifier = new \MongoDB\BSON\ObjectId();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\EmbedTest')
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.pet.owner.id')->equals((string) $identifier);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertArrayHasKey('eO.eO.e1.eO.eP.pO._id', $debug);
        $this->assertEquals($identifier, $debug['eO.eO.e1.eO.eP.pO._id']);
    }

    public function testSelectVsSingleCollectionInheritance()
    {
        $p = new \Documents\SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find('Documents\Project')
                ->select(array('name'))
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\SubProject', $test);
        $this->assertEquals('SubProject', $test->getName());
    }

    public function testEmptySelectVsSingleCollectionInheritance()
    {
        $p = new \Documents\SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find('Documents\Project')
                ->select(array())
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\SubProject', $test);
        $this->assertEquals('SubProject', $test->getName());
    }

    public function testDiscriminatorFieldNotAddedWithoutHydration()
    {
        $p = new \Documents\SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find('Documents\Project')->hydrate(false)
                ->select(array('name'))
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertEquals(array('_id', 'name'), array_keys($test));
    }

    public function testExcludeVsSingleCollectionInheritance()
    {
        $p = new \Documents\SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find('Documents\SubProject')
                ->exclude(array('name', 'issues'))
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\SubProject', $test);
        $this->assertNull($test->getName());
    }

    public function testReadOnly()
    {
        $p = new Person('Maciej');
        $p->pet = new Pet('Blackie', $p);
        $this->dm->persist($p);
        $this->dm->flush();

        $readOnly = $this->dm->createQueryBuilder()
            ->find(Person::class)
            ->field('id')->equals($p->id)
            ->readOnly()
            ->getQuery()->getSingleResult();

        $this->assertNotSame($p, $readOnly);
        $this->assertTrue($this->uow->isInIdentityMap($p));
        $this->assertFalse($this->uow->isInIdentityMap($readOnly));
        $this->assertFalse($this->uow->isInIdentityMap($readOnly->pet));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructorShouldThrowExceptionForInvalidType()
    {
        new Query($this->dm, new ClassMetadata(User::class), $this->getMockCollection(), ['type' => -1], []);
    }

    /**
     * @dataProvider provideQueryTypesThatDoNotReturnAnIterator
     * @expectedException BadMethodCallException
     */
    public function testGetIteratorShouldThrowExceptionWithoutExecutingForTypesThatDoNotReturnAnIterator($type, $method)
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->never())->method($method);

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, ['type' => $type], []);

        $query->getIterator();
    }

    public function provideQueryTypesThatDoNotReturnAnIterator()
    {
        return [
            [Query::TYPE_FIND_AND_UPDATE, 'findOneAndUpdate'],
            [Query::TYPE_FIND_AND_REMOVE, 'findOneAndDelete'],
            [Query::TYPE_INSERT, 'insertOne'],
            [Query::TYPE_UPDATE, 'updateOne'],
            [Query::TYPE_REMOVE, 'deleteOne'],
            [Query::TYPE_COUNT, 'count'],
        ];
    }

    public function testFindAndModifyOptionsAreRenamed()
    {
        $queryArray = [
            'type' => Query::TYPE_FIND_AND_REMOVE,
            'query' => ['type' => 1],
            'select' => ['_id' => 1],
        ];

        $collection = $this->getMockCollection();
        $collection
            ->expects($this->at(0))
            ->method('withOptions')
            ->with(['readPreference' => new ReadPreference('primary')])
            ->will($this->returnSelf());

        $collection
            ->expects($this->at(1))
            ->method('findOneAndDelete')
            ->with(['type' => 1], ['projection' => ['_id' => 1]]);

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);
        $query->execute();
    }

    public function testMapReduceOptionsArePassed()
    {
        $map = 'function() { emit(this.a, 1); }';
        $reduce = 'function(key, values) { return Array.sum(values); }';

        $queryArray = [
            'type' => Query::TYPE_MAP_REDUCE,
            'mapReduce' => [
                'map' => $map,
                'reduce' => $reduce,
                'out' => 'collection',
                'options' => ['jsMode' => true],
            ],
            'limit' => 10,
            'query' => ['type' => 1],
        ];

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('mapReduce')
            ->with($map, $reduce, 'collection', ['type' => 1], ['limit' => 10, 'jsMode' => true]);

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);
        $query->execute();
    }

    public function testWithPrimaryReadPreference()
    {
        $collection = $this->getMockCollection();

        $collection
            ->expects($this->at(0))
            ->method('withOptions')
            ->with(['readPreference' => new ReadPreference('primary')])
            ->will($this->returnSelf());

        $collection->expects($this->at(1))
            ->method('findOneAndDelete')
            ->with(['type' => 1], ['projection' => ['_id' => 1]]);

        $queryArray = [
            'type' => Query::TYPE_FIND_AND_REMOVE,
            'query' => ['type' => 1],
            'select' => ['_id' => 1],
        ];

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);
        $query->execute();
    }

    public function testWithReadPreference()
    {
        $collection = $this->getMockCollection();
        $readPreference = new ReadPreference('secondary', [['dc' => 'east']]);

        $collection
            ->expects($this->never())
            ->method('withOptions');

        $collection
            ->expects($this->once())
            ->method('count')
            ->with(['foo' => 'bar'])
            ->will($this->returnValue(100));

        $queryArray = [
            'type' => Query::TYPE_COUNT,
            'query' => ['foo' => 'bar'],
            'readPreference' => $readPreference,
        ];

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);

        $this->assertEquals(100, $query->execute());
    }

    public function testCountWithOptions()
    {
        $collection = $this->getMockCollection();

        $collection->expects($this->at(0))
            ->method('count')
            ->with(['foo' => 'bar'], ['skip' => 5])
            ->will($this->returnValue(100));

        $queryArray = [
            'type' => Query::TYPE_COUNT,
            'query' => ['foo' => 'bar'],
            'skip' => 5,
        ];

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);

        $this->assertSame(100, $query->execute());
    }

    public function testEagerCursorPreparation()
    {
        $collection = $this->dm->getDocumentCollection(User::class);

        $queryArray = [
            'type' => Query::TYPE_FIND,
            'query' => ['foo' => 'bar'],
            'eagerCursor' => true,
        ];

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);

        $eagerCursor = $query->execute();

        $this->assertInstanceOf(CachingIterator::class, $eagerCursor);
    }

    private function getMockCollection()
    {
        return $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockCursor()
    {
        return $this->getMockBuilder(\MongoDB\Driver\Cursor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockDatabase()
    {
        return $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}

/** @ODM\Document(collection="people") */
class Person
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $firstName;

    /** @ODM\ReferenceOne(storeAs="dbRefWithDb") */
    public $bestFriend;

    /** @ODM\ReferenceOne(storeAs="id", targetDocument="Doctrine\ODM\MongoDB\Tests\Person") */
    public $bestFriendSimple;

    /** @ODM\ReferenceOne */
    public $bestFriendPartial;

    /** @ODM\ReferenceMany(storeAs="dbRefWithDb") */
    public $friends = array();

    /** @ODM\ReferenceMany(storeAs="id", targetDocument="Doctrine\ODM\MongoDB\Tests\Person") */
    public $friendsSimple = array();

    /** @ODM\ReferenceMany */
    public $friendsPartial = array();

    /** @ODM\EmbedOne(targetDocument="Pet") */
    public $pet;

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

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name, Person $owner)
    {
        $this->name = $name;
        $this->owner = $owner;
    }
}

/** @ODM\EmbeddedDocument */
class EmbedTest
{
    /** @ODM\EmbedOne(name="eO", targetDocument="Doctrine\ODM\MongoDB\Tests\EmbedTest") */
    public $embeddedOne;

    /** @ODM\EmbedMany(name="e1", targetDocument="Doctrine\ODM\MongoDB\Tests\EmbedTest") */
    public $embeddedMany;

    /** @ODM\Field(name="n", type="string") */
    public $name;

    /** @ODM\ReferenceOne(name="p", targetDocument="Doctrine\ODM\MongoDB\Tests\Person") */
    public $person;

    /** @ODM\EmbedOne(name="eP", targetDocument="Doctrine\ODM\MongoDB\Tests\Pet") */
    public $pet;
}
