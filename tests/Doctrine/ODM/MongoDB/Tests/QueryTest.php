<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\Phonenumber;
use Documents\Project;
use Documents\SubProject;
use Documents\User;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;
use const DOCTRINE_MONGODB_DATABASE;
use function array_keys;

class QueryTest extends BaseTest
{
    public function testSelectAndSelectSliceOnSameField()
    {
        $qb = $this->dm->createQueryBuilder(Person::class)
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

        $class = Person::class;
        $expression1 = ['firstName' => 'Kris'];
        $expression2 = ['firstName' => 'Chris'];

        $qb = $this->dm->createQueryBuilder($class);
        $qb->addOr($qb->expr()->field('firstName')->equals('Kris'));
        $qb->addOr($qb->expr()->field('firstName')->equals('Chris'));

        $this->assertEquals([
        '$or' => [
            ['firstName' => 'Kris'],
            ['firstName' => 'Chris'],
        ],
        ], $qb->getQueryArray());

        $query = $qb->getQuery();
        $users = $query->execute();

        $this->assertInstanceOf(Iterator::class, $users);
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

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('bestFriend')->references($jon);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals([
            'bestFriend.$ref' => 'people',
            'bestFriend.$id' => new ObjectId($jon->id),
            'bestFriend.$db' => DOCTRINE_MONGODB_DATABASE,
        ], $queryArray);

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

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('bestFriendSimple')->references($jon);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals([
            'bestFriendSimple' => new ObjectId($jon->id),
        ], $queryArray);

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

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('bestFriendPartial')->references($jon);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals([
            'bestFriendPartial.$ref' => 'people',
            'bestFriendPartial.$id' => new ObjectId($jon->id),
        ], $queryArray);

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

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('friends')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals([
            'friends' => [
                '$elemMatch' => [
                    '$ref' => 'people',
                    '$id' => new ObjectId($kris->id),
                    '$db' => DOCTRINE_MONGODB_DATABASE,
                ],
            ],
        ], $queryArray);

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

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('friendsSimple')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals([
            'friendsSimple' =>  new ObjectId($kris->id),
        ], $queryArray);

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

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('friendsPartial')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        $this->assertEquals([
            'friendsPartial' => [
                '$elemMatch' => [
                    '$ref' => 'people',
                    '$id' => new ObjectId($kris->id),
                ],
            ],
        ], $queryArray);

        $query = $qb->getQuery();

        $this->assertCount(1, $query->toArray());
        $this->assertSame($jon, $query->getSingleResult());
    }

    public function testQueryIdIn()
    {
        $user = new User();
        $user->setUsername('jwage');
        $this->dm->persist($user);
        $this->dm->flush();

        $identifier = new ObjectId($user->getId());
        $ids = [$identifier];

        $qb = $this->dm->createQueryBuilder(User::class)
            ->field('_id')->in($ids);
        $query = $qb->getQuery();
        $results = $query->toArray();
        $this->assertCount(1, $results);
    }

    public function testEmbeddedSet()
    {
        $qb = $this->dm->createQueryBuilder(User::class)
            ->insert()
            ->field('testInt')->set('0')
            ->field('intfields.intone')->set('1')
            ->field('intfields.inttwo')->set('2');
        $this->assertEquals(['testInt' => 0, 'intfields' => ['intone' => 1, 'inttwo' => 2]], $qb->getNewObj());
    }

    public function testElemMatch()
    {
        $refId = '000000000000000000000001';

        $qb = $this->dm->createQueryBuilder(User::class);
        $embeddedQb = $this->dm->createQueryBuilder(Phonenumber::class);

        $qb->field('phonenumbers')->elemMatch($embeddedQb->expr()
            ->field('lastCalledBy.id')->equals($refId));
        $query = $qb->getQuery();

        $expectedQuery = ['phonenumbers' => ['$elemMatch' => ['lastCalledBy.$id' => new ObjectId($refId)]]];
        $this->assertEquals($expectedQuery, $query->debug('query'));
    }

    public function testQueryWithMultipleEmbeddedDocuments()
    {
        $qb = $this->dm->createQueryBuilder(EmbedTest::class)
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.name')->equals('Foo');
        $query = $qb->getQuery();
        $this->assertEquals(['eO.eO.e1.eO.n' => 'Foo'], $query->debug('query'));
    }

    public function testQueryWithMultipleEmbeddedDocumentsAndReference()
    {
        $identifier = new ObjectId();

        $qb = $this->dm->createQueryBuilder(EmbedTest::class)
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.pet.owner.id')->equals((string) $identifier);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertArrayHasKey('eO.eO.e1.eO.eP.pO.$id', $debug);
        $this->assertEquals($identifier, $debug['eO.eO.e1.eO.eP.pO.$id']);
    }

    public function testQueryWithMultipleEmbeddedDocumentsAndReferenceUsingDollarSign()
    {
        $identifier = new ObjectId();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__ . '\EmbedTest')
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.pet.owner.$id')->equals((string) $identifier);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertArrayHasKey('eO.eO.e1.eO.eP.pO.$id', $debug);
        $this->assertEquals($identifier, $debug['eO.eO.e1.eO.eP.pO.$id']);
    }

    public function testSelectVsSingleCollectionInheritance()
    {
        $p = new SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find(Project::class)
                ->select(['name'])
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertInstanceOf(SubProject::class, $test);
        $this->assertEquals('SubProject', $test->getName());
    }

    public function testEmptySelectVsSingleCollectionInheritance()
    {
        $p = new SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find(Project::class)
                ->select([])
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertInstanceOf(SubProject::class, $test);
        $this->assertEquals('SubProject', $test->getName());
    }

    public function testDiscriminatorFieldNotAddedWithoutHydration()
    {
        $p = new SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find(Project::class)->hydrate(false)
                ->select(['name'])
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertEquals(['_id', 'name'], array_keys($test));
    }

    public function testExcludeVsSingleCollectionInheritance()
    {
        $p = new SubProject('SubProject');
        $this->dm->persist($p);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder()
                ->find(SubProject::class)
                ->exclude(['name', 'issues'])
                ->field('id')->equals($p->getId())
                ->getQuery()->getSingleResult();
        $this->assertNotNull($test);
        $this->assertInstanceOf(SubProject::class, $test);
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
            ->readOnly(true)
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
            ->method('findOneAndDelete')
            ->with(['type' => 1], ['projection' => ['_id' => 1]]);

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);
        $query->execute();
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

    public function testReadPreference()
    {
        $readPreference = new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED);

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('count')
            ->with(['foo' => 'bar'], ['readPreference' => $readPreference])
            ->will($this->returnValue(0));

        $queryQrray = [
            'type' => Query::TYPE_COUNT,
            'query' => ['foo' => 'bar'],
        ];
        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryQrray, ['readPreference' => $readPreference]);
        $query->execute();
    }

    private function getMockCollection()
    {
        return $this->getMockBuilder(Collection::class)
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
    public $friends = [];

    /** @ODM\ReferenceMany(storeAs="id", targetDocument="Doctrine\ODM\MongoDB\Tests\Person") */
    public $friendsSimple = [];

    /** @ODM\ReferenceMany */
    public $friendsPartial = [];

    /** @ODM\EmbedOne(targetDocument=Pet::class) */
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
