<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use ArrayIterator;
use BadMethodCallException;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\Phonenumber;
use Documents\Project;
use Documents\SubProject;
use Documents\User;
use InvalidArgumentException;
use LogicException;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Traversable;

use function array_keys;
use function iterator_to_array;

use const DOCTRINE_MONGODB_DATABASE;

class QueryTest extends BaseTestCase
{
    public function testSelectAndSelectSliceOnSameField(): void
    {
        $qb = $this->dm->createQueryBuilder(Person::class)
            ->exclude('comments')
            ->select('comments')
            ->selectSlice('comments', 0, 10);

        $query = $qb->getQuery();

        self::assertEquals(['comments' => ['$slice' => [0, 10]]], $query->getQuery()['select']);

        $query = $qb->getQuery();
        $query->execute();
    }

    public function testThatOrAcceptsAnotherQuery(): void
    {
        $kris  = new Person('Kris');
        $chris = new Person('Chris');
        $this->dm->persist($kris);
        $this->dm->persist($chris);
        $this->dm->flush();

        $class       = Person::class;
        $expression1 = ['firstName' => 'Kris'];
        $expression2 = ['firstName' => 'Chris'];

        $qb = $this->dm->createQueryBuilder($class);
        $qb->addOr($qb->expr()->field('firstName')->equals('Kris'));
        $qb->addOr($qb->expr()->field('firstName')->equals('Chris'));

        self::assertEquals([
            '$or' => [
                ['firstName' => 'Kris'],
                ['firstName' => 'Chris'],
            ],
        ], $qb->getQueryArray());

        $query = $qb->getQuery();
        $users = $query->execute();

        self::assertInstanceOf(Iterator::class, $users);
        self::assertCount(2, $users->toArray());
    }

    public function testReferences(): void
    {
        $kris = new Person('Kris');
        $jon  = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $kris->bestFriend = $jon;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('bestFriend')->references($jon);

        $queryArray = $qb->getQueryArray();
        self::assertEquals([
            'bestFriend.$ref' => 'people',
            'bestFriend.$id' => new ObjectId($jon->id),
            'bestFriend.$db' => DOCTRINE_MONGODB_DATABASE,
        ], $queryArray);

        $query = $qb->getQuery();

        self::assertCount(1, $query->toArray());
        self::assertSame($kris, $query->getSingleResult());
    }

    public function testReferencesStoreAsId(): void
    {
        $kris = new Person('Kris');
        $jon  = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $kris->bestFriendSimple = $jon;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('bestFriendSimple')->references($jon);

        $queryArray = $qb->getQueryArray();
        self::assertEquals([
            'bestFriendSimple' => new ObjectId($jon->id),
        ], $queryArray);

        $query = $qb->getQuery();

        self::assertCount(1, $query->toArray());
        self::assertSame($kris, $query->getSingleResult());
    }

    public function testReferencesStoreAsDbRef(): void
    {
        $kris = new Person('Kris');
        $jon  = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $kris->bestFriendPartial = $jon;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('bestFriendPartial')->references($jon);

        $queryArray = $qb->getQueryArray();
        self::assertEquals([
            'bestFriendPartial.$ref' => 'people',
            'bestFriendPartial.$id' => new ObjectId($jon->id),
        ], $queryArray);

        $query = $qb->getQuery();

        self::assertCount(1, $query->toArray());
        self::assertSame($kris, $query->getSingleResult());
    }

    public function testIncludesReferenceToWithStoreAsDbRefWithDb(): void
    {
        $kris = new Person('Kris');
        $jon  = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $jon->friends[] = $kris;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('friends')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        self::assertEquals([
            'friends' => [
                '$elemMatch' => [
                    '$ref' => 'people',
                    '$id' => new ObjectId($kris->id),
                    '$db' => DOCTRINE_MONGODB_DATABASE,
                ],
            ],
        ], $queryArray);

        $query = $qb->getQuery();

        self::assertCount(1, $query->toArray());
        self::assertSame($jon, $query->getSingleResult());
    }

    public function testIncludesReferenceToWithStoreAsId(): void
    {
        $kris   = new Person('Kris');
        $jon    = new Person('Jon');
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
        self::assertEquals([
            'friendsSimple' =>  new ObjectId($kris->id),
        ], $queryArray);

        $query = $qb->getQuery();

        self::assertCount(1, $query->toArray());
        self::assertSame($jon, $query->getSingleResult());
    }

    public function testIncludesReferenceToWithStoreAsDbRef(): void
    {
        $kris = new Person('Kris');
        $jon  = new Person('Jon');

        $this->dm->persist($kris);
        $this->dm->persist($jon);
        $this->dm->flush();

        $jon->friendsPartial[] = $kris;
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(Person::class);
        $qb->field('friendsPartial')->includesReferenceTo($kris);

        $queryArray = $qb->getQueryArray();
        self::assertEquals([
            'friendsPartial' => [
                '$elemMatch' => [
                    '$ref' => 'people',
                    '$id' => new ObjectId($kris->id),
                ],
            ],
        ], $queryArray);

        $query = $qb->getQuery();

        self::assertCount(1, $query->toArray());
        self::assertSame($jon, $query->getSingleResult());
    }

    public function testQueryIdIn(): void
    {
        $user = new User();
        $user->setUsername('jwage');
        $this->dm->persist($user);
        $this->dm->flush();

        $identifier = new ObjectId($user->getId());
        $ids        = [$identifier];

        $qb      = $this->dm->createQueryBuilder(User::class)
            ->field('_id')->in($ids);
        $query   = $qb->getQuery();
        $results = $query->toArray();
        self::assertCount(1, $results);
    }

    public function testEmbeddedSet(): void
    {
        $qb = $this->dm->createQueryBuilder(User::class)
            ->insert()
            ->field('testInt')->set('0')
            ->field('intfields.intone')->set('1')
            ->field('intfields.inttwo')->set('2');
        self::assertEquals(['testInt' => 0, 'intfields' => ['intone' => 1, 'inttwo' => 2]], $qb->getNewObj());
    }

    public function testElemMatch(): void
    {
        $refId = '000000000000000000000001';

        $qb         = $this->dm->createQueryBuilder(User::class);
        $embeddedQb = $this->dm->createQueryBuilder(Phonenumber::class);

        $qb->field('phonenumbers')->elemMatch($embeddedQb->expr()
            ->field('lastCalledBy.id')->equals($refId));
        $query = $qb->getQuery();

        $expectedQuery = ['phonenumbers' => ['$elemMatch' => ['lastCalledBy.$id' => new ObjectId($refId)]]];
        self::assertEquals($expectedQuery, $query->debug('query'));
    }

    public function testQueryWithMultipleEmbeddedDocuments(): void
    {
        $qb    = $this->dm->createQueryBuilder(EmbedTest::class)
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.name')->equals('Foo');
        $query = $qb->getQuery();
        self::assertEquals(['eO.eO.e1.eO.n' => 'Foo'], $query->debug('query'));
    }

    public function testQueryWithMultipleEmbeddedDocumentsAndReference(): void
    {
        $identifier = new ObjectId();

        $qb    = $this->dm->createQueryBuilder(EmbedTest::class)
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.pet.owner.id')->equals((string) $identifier);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertArrayHasKey('eO.eO.e1.eO.eP.pO.$id', $debug);
        self::assertEquals($identifier, $debug['eO.eO.e1.eO.eP.pO.$id']);
    }

    public function testQueryWithMultipleEmbeddedDocumentsAndReferenceUsingDollarSign(): void
    {
        $identifier = new ObjectId();

        $qb    = $this->dm->createQueryBuilder(EmbedTest::class)
            ->find()
            ->field('embeddedOne.embeddedOne.embeddedMany.embeddedOne.pet.owner.$id')->equals((string) $identifier);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertArrayHasKey('eO.eO.e1.eO.eP.pO.$id', $debug);
        self::assertEquals($identifier, $debug['eO.eO.e1.eO.eP.pO.$id']);
    }

    public function testSelectVsSingleCollectionInheritance(): void
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
        self::assertNotNull($test);
        self::assertInstanceOf(SubProject::class, $test);
        self::assertEquals('SubProject', $test->getName());
    }

    public function testEmptySelectVsSingleCollectionInheritance(): void
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
        self::assertNotNull($test);
        self::assertInstanceOf(SubProject::class, $test);
        self::assertEquals('SubProject', $test->getName());
    }

    public function testDiscriminatorFieldNotAddedWithoutHydration(): void
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
        self::assertNotNull($test);
        self::assertEquals(['_id', 'name'], array_keys($test));
    }

    public function testExcludeVsSingleCollectionInheritance(): void
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
        self::assertNotNull($test);
        self::assertInstanceOf(SubProject::class, $test);
        self::assertNull($test->getName());
    }

    public function testReadOnly(): void
    {
        $p      = new Person('Maciej');
        $p->pet = new Pet('Blackie', $p);
        $this->dm->persist($p);
        $this->dm->flush();

        $readOnly = $this->dm->createQueryBuilder()
            ->find(Person::class)
            ->field('id')->equals($p->id)
            ->readOnly(true)
            ->getQuery()->getSingleResult();

        self::assertInstanceOf(Person::class, $readOnly);
        self::assertNotSame($p, $readOnly);
        self::assertTrue($this->uow->isInIdentityMap($p));
        self::assertFalse($this->uow->isInIdentityMap($readOnly));
        self::assertFalse($this->uow->isInIdentityMap($readOnly->pet));
    }

    public function testConstructorShouldThrowExceptionForInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Query($this->dm, new ClassMetadata(User::class), $this->getMockCollection(), ['type' => -1], []);
    }

    /** @param Query::TYPE_* $type */
    #[DataProvider('provideQueryTypesThatDoNotReturnAnIterator')]
    public function testGetIteratorShouldThrowExceptionWithoutExecutingForTypesThatDoNotReturnAnIterator(int $type, string $method): void
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->never())->method($method);

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, ['type' => $type], []);

        $this->expectException(BadMethodCallException::class);
        $query->getIterator();
    }

    public static function provideQueryTypesThatDoNotReturnAnIterator(): array
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

    public function testFindAndModifyOptionsAreRenamed(): void
    {
        $queryArray = [
            'type' => Query::TYPE_FIND_AND_REMOVE,
            'query' => ['type' => 1],
            'select' => ['_id' => 1],
        ];

        $collection = $this->getMockCollection();
        $collection
            ->expects($this->once())
            ->method('findOneAndDelete')
            ->with(['type' => 1], ['projection' => ['_id' => 1]]);

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);
        $query->execute();
    }

    public function testCountOptionInheritance(): void
    {
        $nearest            = new ReadPreference('nearest');
        $secondaryPreferred = new ReadPreference('secondaryPreferred');

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('count')
            ->with(['foo' => 'bar'], ['maxTimeMS' => 100, 'skip' => 5, 'readPreference' => $nearest])
            ->willReturn(100);

        $queryArray = [
            'type' => Query::TYPE_COUNT,
            'query' => ['foo' => 'bar'],
            'skip' => 5,
            'readPreference' => $nearest,
        ];

        $options = [
            'maxTimeMS' => 100,
            'readPreference' => $secondaryPreferred,
        ];

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, $options);

        self::assertSame(100, $query->execute());
    }

    public function testFindWithHint(): void
    {
        $cursor = $this->createMock(Traversable::class);

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('find')
            ->with(['foo' => 'bar'], ['hint' => 'foo'])
            ->will($this->returnValue($cursor));

        // Using QueryBuilder->find adds hint to the query array
        $queryArray = [
            'type' => Query::TYPE_FIND,
            'query' => ['foo' => 'bar'],
            'hint' => 'foo',
        ];

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, []);

        /* Do not expect the same object returned by Collection::find(), since
         * Query::makeIterator() wraps the return value with CachingIterator. */
        self::assertInstanceOf(Traversable::class, $query->execute());
    }

    public function testFindOptionInheritance(): void
    {
        $nearest            = new ReadPreference('nearest');
        $secondaryPreferred = new ReadPreference('secondaryPreferred');

        $cursor = $this->createMock(Traversable::class);

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('find')
            ->with(['foo' => 'bar'], ['maxTimeMS' => 100, 'skip' => 5, 'readPreference' => $nearest])
            ->willReturn($cursor);

        $queryArray = [
            'type' => Query::TYPE_FIND,
            'query' => ['foo' => 'bar'],
            'skip' => 5,
            'readPreference' => $nearest,
        ];

        $options = [
            'maxTimeMS' => 100,
            'readPreference' => $secondaryPreferred,
        ];

        $query = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray, $options);

        /* Do not expect the same object returned by Collection::find(), since
         * Query::makeIterator() wraps the return value with CachingIterator. */
        self::assertInstanceOf(Traversable::class, $query->execute());
    }

    public function testNonRewindable(): void
    {
        $cursor = new ArrayIterator(['foo']);

        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('find')
            ->with([], [])
            ->willReturn($cursor);

        $queryArray = [
            'type' => Query::TYPE_FIND,
            'query' => [],
        ];
        $query      = new Query($this->dm, new ClassMetadata(User::class), $collection, $queryArray);
        $query->setHydrate(false);
        $query->setRewindable(false);

        $iterator = $query->execute();

        self::assertInstanceOf(UnrewindableIterator::class, $iterator);
        iterator_to_array($iterator);

        $this->expectException(LogicException::class);
        iterator_to_array($iterator);
    }

    /** @return MockObject&Collection */
    private function getMockCollection()
    {
        return $this->createMock(Collection::class);
    }
}

#[ODM\Document(collection: 'people')]
class Person
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $firstName;

    /** @var Person|null */
    #[ODM\ReferenceOne(storeAs: 'dbRefWithDb')]
    public $bestFriend;

    /** @var Person|null */
    #[ODM\ReferenceOne(storeAs: 'id', targetDocument: self::class)]
    public $bestFriendSimple;

    /** @var Person|null */
    #[ODM\ReferenceOne]
    public $bestFriendPartial;

    /** @var DoctrineCollection<int, Person>|array<Person> */
    #[ODM\ReferenceMany(storeAs: 'dbRefWithDb')]
    public $friends = [];

    /** @var DoctrineCollection<int, Person>|array<Person> */
    #[ODM\ReferenceMany(storeAs: 'id', targetDocument: self::class)]
    public $friendsSimple = [];

    /** @var DoctrineCollection<int, Person>|array<Person> */
    #[ODM\ReferenceMany]
    public $friendsPartial = [];

    /** @var Pet|null */
    #[ODM\EmbedOne(targetDocument: Pet::class)]
    public $pet;

    public function __construct(string $firstName)
    {
        $this->firstName = $firstName;
    }
}

#[ODM\EmbeddedDocument]
class Pet
{
    /** @var Person|null */
    #[ODM\ReferenceOne(name: 'pO', targetDocument: Person::class)]
    public $owner;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(string $name, Person $owner)
    {
        $this->name  = $name;
        $this->owner = $owner;
    }
}

#[ODM\EmbeddedDocument]
class EmbedTest
{
    /** @var EmbedTest|null */
    #[ODM\EmbedOne(name: 'eO', targetDocument: self::class)]
    public $embeddedOne;

    /** @var DoctrineCollection<int, EmbedTest> */
    #[ODM\EmbedMany(name: 'e1', targetDocument: self::class)]
    public $embeddedMany;

    /** @var string|null */
    #[ODM\Field(name: 'n', type: 'string')]
    public $name;

    /** @var Person|null */
    #[ODM\ReferenceOne(name: 'p', targetDocument: Person::class)]
    public $person;

    /** @var Pet|null */
    #[ODM\EmbedOne(name: 'eP', targetDocument: Pet::class)]
    public $pet;
}
