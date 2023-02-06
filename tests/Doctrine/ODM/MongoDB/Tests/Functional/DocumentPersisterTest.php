<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Closure;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use Documents\Article;
use Generator;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use ReflectionProperty;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class DocumentPersisterTest extends BaseTest
{
    /** @var class-string<DocumentPersisterTestDocument> */
    private string $class;

    /** @var DocumentPersister<DocumentPersisterTestDocument> */
    private DocumentPersister $documentPersister;

    public function setUp(): void
    {
        parent::setUp();

        $this->class = DocumentPersisterTestDocument::class;

        $collection = $this->dm->getDocumentCollection($this->class);
        $collection->drop();

        foreach (['a', 'b', 'c', 'd'] as $name) {
            $document = ['dbName' => $name];
            $collection->insertOne($document);
        }

        $this->documentPersister = $this->uow->getDocumentPersister($this->class);
    }

    public function testExecuteUpsertShouldNeverReplaceDocuments(): void
    {
        $originalData = $this->dm->getDocumentCollection($this->class)->findOne();

        $document     = new DocumentPersisterTestDocument();
        $document->id = $originalData['_id'];

        $this->dm->persist($document);
        $this->dm->flush();

        $updatedData = $this->dm->getDocumentCollection($this->class)->findOne(['_id' => $originalData['_id']]);

        self::assertEquals($originalData, $updatedData);
    }

    public function testExistsReturnsTrueForExistentDocuments(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $name) {
            $document = $this->documentPersister->load(['name' => $name]);
            self::assertTrue($this->documentPersister->exists($document));
        }
    }

    public function testExistsReturnsFalseForNonexistentDocuments(): void
    {
        $document     = new DocumentPersisterTestDocument();
        $document->id = new ObjectId();

        self::assertFalse($this->documentPersister->exists($document));
    }

    public function testLoadPreparesCriteriaAndSort(): void
    {
        $criteria = ['name' => ['$in' => ['a', 'b']]];
        $sort     = ['name' => -1];

        $document = $this->documentPersister->load($criteria, null, [], 0, $sort);

        self::assertInstanceOf($this->class, $document);
        self::assertEquals('b', $document->name);
    }

    public function testLoadAllPreparesCriteriaAndSort(): void
    {
        $criteria = ['name' => ['$in' => ['a', 'b']]];
        $sort     = ['name' => -1];

        $cursor    = $this->documentPersister->loadAll($criteria, $sort);
        $documents = $cursor->toArray();

        self::assertInstanceOf($this->class, $documents[0]);
        self::assertEquals('b', $documents[0]->name);
        self::assertInstanceOf($this->class, $documents[1]);
        self::assertEquals('a', $documents[1]->name);
    }

    public function testLoadAllWithSortLimitAndSkip(): void
    {
        $sort = ['name' => -1];

        $cursor    = $this->documentPersister->loadAll([], $sort, 1, 2);
        $documents = $cursor->toArray();

        self::assertInstanceOf($this->class, $documents[0]);
        self::assertEquals('b', $documents[0]->name);
        self::assertCount(1, $documents);
    }

    /** @dataProvider getTestPrepareFieldNameData */
    public function testPrepareFieldName(string $fieldName, string $expected): void
    {
        self::assertEquals($expected, $this->documentPersister->prepareFieldName($fieldName));
    }

    public function getTestPrepareFieldNameData(): array
    {
        return [
            ['name', 'dbName'],
            ['association', 'associationName'],
            ['association.id', 'associationName._id'],
            ['association.nested', 'associationName.nestedName'],
            ['association.nested.$id', 'associationName.nestedName.$id'],
            ['association.nested._id', 'associationName.nestedName._id'],
            ['association.nested.id', 'associationName.nestedName._id'],
            ['association.nested.association.nested.$id', 'associationName.nestedName.associationName.nestedName.$id'],
            ['association.nested.association.nested.id', 'associationName.nestedName.associationName.nestedName._id'],
            ['association.nested.association.nested.firstName', 'associationName.nestedName.associationName.nestedName.firstName'],
        ];
    }

    public function testCurrentDateInQuery(): void
    {
        $qb = $this->dm->createQueryBuilder(Article::class)
            ->updateMany()
            ->field('createdAt')->currentDate();

        self::assertSame(
            ['$currentDate' => ['createdAt' => ['$type' => 'date']]],
            $qb->getQuery()->debug('newObj'),
        );
    }

    public function testExistsInQuery(): void
    {
        $qb = $this->dm->createQueryBuilder(Article::class)
            ->field('title')->exists(false)
            ->field('createdAt')->exists(true);

        self::assertSame(
            [
                'title' => ['$exists' => false],
                'createdAt' => ['$exists' => true],
            ],
            $qb->getQuery()->debug('query'),
        );
    }

    /**
     * @param array<array-key, string> $hashId
     *
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithHashId(array $hashId): void
    {
        $class             = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value    = ['_id' => $hashId];
        $expected = ['_id' => (object) $hashId];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @param array<array-key, string> $hashId
     *
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithHashIdAndInOperators(array $hashId): void
    {
        $class             = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value    = ['_id' => ['$exists' => true]];
        $expected = ['_id' => ['$exists' => true]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['_id' => ['$elemMatch' => $hashId]];
        $expected = ['_id' => ['$elemMatch' => $hashId]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['_id' => ['$in' => [$hashId]]];
        $expected = ['_id' => ['$in' => [(object) $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['_id' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['_id' => ['$not' => ['$elemMatch' => $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['_id' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['_id' => ['$not' => ['$in' => [(object) $hashId]]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $query
     *
     * @dataProvider queryProviderForCustomTypeId
     */
    public function testPrepareQueryOrNewObjWithCustomTypedId(array $expected, array $query): void
    {
        $class             = DocumentPersisterTestDocumentWithCustomId::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        Type::registerType('DocumentPersisterCustomId', DocumentPersisterCustomIdType::class);

        self::assertEquals(
            $expected,
            $documentPersister->prepareQueryOrNewObj($query),
        );
    }

    /** @dataProvider queryProviderForDocumentWithReferenceToDocumentWithCustomTypedId */
    public function testPrepareQueryOrNewObjWithReferenceToDocumentWithCustomTypedId(Closure $getTestCase): void
    {
        Type::registerType('DocumentPersisterCustomId', DocumentPersisterCustomIdType::class);

        $class             = DocumentPersisterTestDocumentWithReferenceToDocumentWithCustomId::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        ['query' => $query, 'expected' => $expected] = $getTestCase($this->dm);

        self::assertEquals(
            $expected,
            $documentPersister->prepareQueryOrNewObj($query),
        );
    }

    public function provideHashIdentifiers(): array
    {
        return [
            [['key' => 'value']],
            [[0 => 'first', 1 => 'second']],
            [['$ref' => 'ref', '$id' => 'id']],
        ];
    }

    public function testPrepareQueryOrNewObjWithSimpleReferenceToTargetDocumentWithNormalIdType(): void
    {
        $class             = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new ObjectId();

        $value    = ['simpleRef' => (string) $id];
        $expected = ['simpleRef' => $id];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$exists' => true]];
        $expected = ['simpleRef' => ['$exists' => true]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$elemMatch' => (string) $id]];
        $expected = ['simpleRef' => ['$elemMatch' => $id]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$in' => [(string) $id]]];
        $expected = ['simpleRef' => ['$in' => [$id]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$not' => ['$elemMatch' => (string) $id]]];
        $expected = ['simpleRef' => ['$not' => ['$elemMatch' => $id]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$not' => ['$in' => [(string) $id]]]];
        $expected = ['simpleRef' => ['$not' => ['$in' => [$id]]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    public static function queryProviderForCustomTypeId(): Generator
    {
        $objectIdString  = (string) new ObjectId();
        $objectIdString2 = (string) new ObjectId();

        $customId  = DocumentPersisterCustomTypedId::fromString($objectIdString);
        $customId2 = DocumentPersisterCustomTypedId::fromString($objectIdString2);

        yield 'Direct comparison' => [
            'expected' => ['_id' => new ObjectId($objectIdString)],
            'query' => ['id' => $customId],
        ];

        yield 'Operator with single value' => [
            'expected' => ['_id' => ['$ne' => new ObjectId($objectIdString)]],
            'query' => ['id' => ['$ne' => $customId]],
        ];

        yield 'Operator with multiple values' => [
            'expected' => ['_id' => ['$in' => [new ObjectId($objectIdString), new ObjectId($objectIdString2)]]],
            'query' => ['id' => ['$in' => [$customId, $customId2]]],
        ];
    }

    public static function queryProviderForDocumentWithReferenceToDocumentWithCustomTypedId(): Generator
    {
        $getReference = static function (DocumentManager $dm): DocumentPersisterTestDocumentWithCustomId {
            $objectIdString = (string) new ObjectId();
            $customId       = DocumentPersisterCustomTypedId::fromString($objectIdString);

            return $dm->getReference(
                DocumentPersisterTestDocumentWithCustomId::class,
                $customId,
            );
        };

        yield 'Direct comparison' => [
            static function (DocumentManager $dm) use ($getReference): array {
                $ref = $getReference($dm);

                return [
                    'query' => ['documentWithCustomId' => $ref],
                    'expected' => ['documentWithCustomId' => new ObjectId($ref->getId()->toString())],
                ];
            },
        ];

        yield 'Operator with single value' => [
            static function (DocumentManager $dm) use ($getReference): array {
                $ref = $getReference($dm);

                return [
                    'query' => ['documentWithCustomId' => ['$ne' => $ref]],
                    'expected' => ['documentWithCustomId' => ['$ne' => new ObjectId($ref->getId()->toString())]],
                ];
            },
        ];

        yield 'Operator with multiple values' => [
            static function (DocumentManager $dm) use ($getReference): array {
                $ref1 = $getReference($dm);
                $ref2 = $getReference($dm);

                return [
                    'query' => ['documentWithCustomId' => ['$in' => [$ref1, $ref2]]],
                    'expected' => [
                        'documentWithCustomId' => [
                            '$in' => [
                                new ObjectId($ref1->getId()->toString()),
                                new ObjectId($ref2->getId()->toString()),
                            ],
                        ],
                    ],
                ];
            },
        ];
    }

    /**
     * @param array<array-key, string> $hashId
     *
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithSimpleReferenceToTargetDocumentWithHashIdType(array $hashId): void
    {
        $class             = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value    = ['simpleRef' => $hashId];
        $expected = ['simpleRef' => (object) $hashId];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$exists' => true]];
        $expected = ['simpleRef' => ['$exists' => true]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$elemMatch' => $hashId]];
        $expected = ['simpleRef' => ['$elemMatch' => $hashId]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$in' => [$hashId]]];
        $expected = ['simpleRef' => ['$in' => [(object) $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['simpleRef' => ['$not' => ['$elemMatch' => $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['simpleRef' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['simpleRef' => ['$not' => ['$in' => [(object) $hashId]]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    public function testPrepareQueryOrNewObjWithDBRefReferenceToTargetDocumentWithNormalIdType(): void
    {
        $class             = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new ObjectId();

        $value    = ['complexRef.id' => (string) $id];
        $expected = ['complexRef.$id' => $id];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$exists' => true]];
        $expected = ['complexRef.$id' => ['$exists' => true]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$elemMatch' => (string) $id]];
        $expected = ['complexRef.$id' => ['$elemMatch' => $id]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$in' => [(string) $id]]];
        $expected = ['complexRef.$id' => ['$in' => [$id]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$not' => ['$elemMatch' => (string) $id]]];
        $expected = ['complexRef.$id' => ['$not' => ['$elemMatch' => $id]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$not' => ['$in' => [(string) $id]]]];
        $expected = ['complexRef.$id' => ['$not' => ['$in' => [$id]]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @param array<array-key, string> $hashId
     *
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithDBRefReferenceToTargetDocumentWithHashIdType(array $hashId): void
    {
        $class             = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value    = ['complexRef.id' => $hashId];
        $expected = ['complexRef.$id' => (object) $hashId];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$exists' => true]];
        $expected = ['complexRef.$id' => ['$exists' => true]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$elemMatch' => $hashId]];
        $expected = ['complexRef.$id' => ['$elemMatch' => $hashId]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$in' => [$hashId]]];
        $expected = ['complexRef.$id' => ['$in' => [(object) $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['complexRef.$id' => ['$not' => ['$elemMatch' => $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['complexRef.id' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['complexRef.$id' => ['$not' => ['$in' => [(object) $hashId]]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $query
     *
     * @dataProvider queryProviderForComplexRefWithObjectValue
     */
    public function testPrepareQueryOrNewObjWithComplexRefToTargetDocumentFieldWithObjectValue(array $expected, array $query): void
    {
        $class             = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        self::assertEquals(
            $expected,
            $documentPersister->prepareQueryOrNewObj($query),
        );
    }

    public static function queryProviderForComplexRefWithObjectValue(): Generator
    {
        yield 'Direct comparison' => [
            'expected' => ['complexRef.date' => new UTCDateTime(1590710400000)],
            'query' => ['complexRef.date' => DateTime::createFromFormat('U', '1590710400')],
        ];

        yield 'Operator with single value' => [
            'expected' => ['complexRef.date' => ['$ne' => new UTCDateTime(1590710400000)]],
            'query' => ['complexRef.date' => ['$ne' => DateTime::createFromFormat('U', '1590710400')]],
        ];

        yield 'Operator with multiple values' => [
            'expected' => ['complexRef.date' => ['$in' => [new UTCDateTime(1590710400000), new UTCDateTime(1590796800000)]]],
            'query' => ['complexRef.date' => ['$in' => [DateTime::createFromFormat('U', '1590710400'), DateTime::createFromFormat('U', '1590796800')]]],
        ];

        yield 'Nested operator' => [
            'expected' => ['complexRef.date' => ['$not' => ['$in' => [new UTCDateTime(1590710400000), new UTCDateTime(1590796800000)]]]],
            'query' => ['complexRef.date' => ['$not' => ['$in' => [DateTime::createFromFormat('U', '1590710400'), DateTime::createFromFormat('U', '1590796800')]]]],
        ];
    }

    public function testPrepareQueryOrNewObjWithEmbeddedReferenceToTargetDocumentWithNormalIdType(): void
    {
        $class             = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new ObjectId();

        $value    = ['embeddedRef.id' => (string) $id];
        $expected = ['embeddedRef.id' => $id];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$exists' => true]];
        $expected = ['embeddedRef.id' => ['$exists' => true]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$elemMatch' => (string) $id]];
        $expected = ['embeddedRef.id' => ['$elemMatch' => $id]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$in' => [(string) $id]]];
        $expected = ['embeddedRef.id' => ['$in' => [$id]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$not' => ['$elemMatch' => (string) $id]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$elemMatch' => $id]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$not' => ['$in' => [(string) $id]]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$in' => [$id]]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @param array<array-key, string> $hashId
     *
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithEmbeddedReferenceToTargetDocumentWithHashIdType(array $hashId): void
    {
        $class             = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value    = ['embeddedRef.id' => $hashId];
        $expected = ['embeddedRef.id' => (object) $hashId];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$exists' => true]];
        $expected = ['embeddedRef.id' => ['$exists' => true]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$elemMatch' => $hashId]];
        $expected = ['embeddedRef.id' => ['$elemMatch' => $hashId]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$in' => [$hashId]]];
        $expected = ['embeddedRef.id' => ['$in' => [(object) $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$elemMatch' => $hashId]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value    = ['embeddedRef.id' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$in' => [(object) $hashId]]]];

        self::assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /** @return array */
    public static function dataProviderTestWriteConcern(): array
    {
        return [
            'default' => [
                'className' => DocumentPersisterTestDocument::class,
                'writeConcern' => 1,
            ],
            'acknowledged' => [
                'className' => DocumentPersisterWriteConcernAcknowledged::class,
                'writeConcern' => 1,
            ],
            'unacknowledged' => [
                'className' => DocumentPersisterWriteConcernUnacknowledged::class,
                'writeConcern' => 0,
            ],
            'majority' => [
                'className' => DocumentPersisterWriteConcernMajority::class,
                'writeConcern' => 'majority',
            ],
        ];
    }

    /**
     * @param int|string $writeConcern
     * @psalm-param class-string $class
     *
     * @dataProvider dataProviderTestWriteConcern
     */
    public function testExecuteInsertsRespectsWriteConcern(string $class, $writeConcern): void
    {
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('insertMany')
            ->with($this->isType('array'), $this->logicalAnd($this->arrayHasKey('writeConcern'), $this->containsEqual(new WriteConcern($writeConcern))));

        $reflectionProperty = new ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();
    }

    /**
     * @param int|string $writeConcern
     * @psalm-param class-string $class
     *
     * @dataProvider dataProviderTestWriteConcern
     */
    public function testExecuteUpsertsRespectsWriteConcern(string $class, $writeConcern): void
    {
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('updateOne')
            ->with($this->isType('array'), $this->isType('array'), $this->logicalAnd($this->arrayHasKey('writeConcern'), $this->containsEqual(new WriteConcern($writeConcern))));

        $reflectionProperty = new ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument     = new $class();
        $testDocument->id = new ObjectId();
        $this->dm->persist($testDocument);
        $this->dm->flush();
    }

    /**
     * @param int|string $writeConcern
     * @psalm-param class-string $class
     *
     * @dataProvider dataProviderTestWriteConcern
     */
    public function testRemoveRespectsWriteConcern(string $class, $writeConcern): void
    {
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('deleteOne')
            ->with($this->isType('array'), $this->logicalAnd($this->arrayHasKey('writeConcern'), $this->containsEqual(new WriteConcern($writeConcern))));

        $reflectionProperty = new ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();

        $this->dm->remove($testDocument);
        $this->dm->flush();
    }

    public function testDefaultWriteConcernIsRespected(): void
    {
        $class             = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('insertMany')
            ->with($this->isType('array'), $this->equalTo(['writeConcern' => new WriteConcern(0)]));

        $reflectionProperty = new ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $this->dm->getConfiguration()->setDefaultCommitOptions(['writeConcern' => new WriteConcern(0)]);

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();
    }

    public function testDefaultWriteConcernIsRespectedBackwardCompatibility(): void
    {
        $class             = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('insertMany')
            ->with($this->isType('array'), $this->equalTo(['writeConcern' => new WriteConcern(0)]));

        $reflectionProperty = new ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $this->dm->getConfiguration()->setDefaultCommitOptions(['w' => 0]);

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();
    }

    public function testVersionIncrementOnUpdateSuccess(): void
    {
        $testDocument = new DocumentPersisterTestDocumentWithVersion();
        $this->dm->persist($testDocument);
        $this->dm->flush();

        $testDocument->name = 'test';
        $this->dm->persist($testDocument);
        $this->dm->flush();

        self::assertEquals(2, $testDocument->revision);
    }

    public function testNoVersionIncrementOnUpdateFailure(): void
    {
        $class = DocumentPersisterTestDocumentWithVersion::class;

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();

        $this->dm->getDocumentCollection($class)->updateOne([], ['$inc' => ['revision' => 1]]);

        $testDocument->name = 'test';
        $this->dm->persist($testDocument);
        $this->expectException(LockException::class);
        $this->dm->flush();
    }
}

/** @ODM\Document */
class DocumentPersisterTestDocument
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
     */
    public $id;

    /**
     * @ODM\Field(name="dbName", type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedOne(
     *     targetDocument=Doctrine\ODM\MongoDB\Tests\Functional\AbstractDocumentPersisterTestDocumentAssociation::class,
     *     discriminatorField="type",
     *     name="associationName"
     * )
     *
     * @var AbstractDocumentPersisterTestDocumentAssociation|null
     */
    public $association;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="id")
     *
     * @var DocumentPersisterTestHashIdDocument|null
     */
    public $simpleRef;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="dbRef")
     *
     * @var DocumentPersisterTestHashIdDocument|null
     */
    public $semiComplexRef;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="dbRefWithDb")
     *
     * @var DocumentPersisterTestHashIdDocument|null
     */
    public $complexRef;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="ref")
     *
     * @var DocumentPersisterTestHashIdDocument|null
     */
    public $embeddedRef;
}

/** @ODM\Document */
class DocumentPersisterTestDocumentWithVersion
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(name="dbName", type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Version @ODM\Field(type="int")
     *
     * @var int
     */
    public $revision = 1;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({
 *     "reference"="Doctrine\ODM\MongoDB\Tests\Functional\DocumentPersisterTestDocumentReference",
 *     "embed"="Doctrine\ODM\MongoDB\Tests\Functional\DocumentPersisterTestDocumentEmbed"
 * })
 */
abstract class AbstractDocumentPersisterTestDocumentAssociation
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(name="nestedName")
     *
     * @var object|null
     */
    public $nested;

    /**
     * @ODM\EmbedOne(
     *     targetDocument=Doctrine\ODM\MongoDB\Tests\Functional\AbstractDocumentPersisterTestDocumentAssociation::class,
     *     discriminatorField="type",
     *     name="associationName"
     * )
     *
     * @var AbstractDocumentPersisterTestDocumentAssociation|null
     */
    public $association;
}

/** @ODM\EmbeddedDocument */
class DocumentPersisterTestDocumentReference extends AbstractDocumentPersisterTestDocumentAssociation
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(name="nestedName")
     *
     * @var object|null
     */
    public $nested;
}

/** @ODM\EmbeddedDocument */
class DocumentPersisterTestDocumentEmbed extends AbstractDocumentPersisterTestDocumentAssociation
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(name="nestedName")
     *
     * @var object|null
     */
    public $nested;
}

/** @ODM\Document */
class DocumentPersisterTestHashIdDocument
{
    /**
     * @ODM\Id(strategy="none", options={"type"="hash"})
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="id")
     *
     * @var DocumentPersisterTestDocument|null
     */
    public $simpleRef;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="dbRef")
     *
     * @var DocumentPersisterTestDocument|null
     */
    public $semiComplexRef;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="dbRefWithDb")
     *
     * @var DocumentPersisterTestDocument|null
     */
    public $complexRef;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="ref")
     *
     * @var DocumentPersisterTestDocument|null
     */
    public $embeddedRef;
}

/** @ODM\Document(writeConcern="majority") */
class DocumentPersisterWriteConcernMajority
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/** @ODM\Document(writeConcern=0) */
class DocumentPersisterWriteConcernUnacknowledged
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/** @ODM\Document(writeConcern=1) */
class DocumentPersisterWriteConcernAcknowledged
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

final class DocumentPersisterCustomTypedId
{
    /** @var string */
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        return new static($value);
    }

    public static function generate(): self
    {
        return new static((string) (new ObjectId()));
    }
}

final class DocumentPersisterCustomIdType extends Type
{
    use ClosureToPHP;

    public function convertToDatabaseValue($value)
    {
        if ($value instanceof ObjectId) {
            return $value;
        }

        if ($value instanceof DocumentPersisterCustomTypedId) {
            return new ObjectId($value->toString());
        }

        throw self::createException($value);
    }

    public function convertToPHPValue($value)
    {
        if ($value instanceof DocumentPersisterCustomTypedId) {
            return $value;
        }

        if ($value instanceof ObjectId) {
            return DocumentPersisterCustomTypedId::fromString((string) $value);
        }

        throw self::createException($value);
    }

    /** @param mixed $value */
    private static function createException($value): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf(
                'Expected "%s" or "%s", got "%s"',
                DocumentPersisterCustomTypedId::class,
                ObjectId::class,
                is_object($value) ? get_class($value) : gettype($value),
            ),
        );
    }
}

/** @ODM\Document() */
class DocumentPersisterTestDocumentWithCustomId
{
    /**
     * @ODM\Id(strategy="NONE", type="DocumentPersisterCustomId")
     *
     * @var DocumentPersisterCustomTypedId
     */
    private $id;

    public function __construct(DocumentPersisterCustomTypedId $id)
    {
        $this->id = $id;
    }

    public function getId(): DocumentPersisterCustomTypedId
    {
        return $this->id;
    }
}

/** @ODM\Document() */
class DocumentPersisterTestDocumentWithReferenceToDocumentWithCustomId
{
    /**
     * @ODM\Id()
     *
     * @var DocumentPersisterCustomTypedId
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocumentWithCustomId::class, storeAs="id")
     *
     * @var DocumentPersisterTestDocumentWithCustomId
     */
    private $documentWithCustomId;

    public function __construct(DocumentPersisterTestDocumentWithCustomId $documentWithCustomId)
    {
        $this->documentWithCustomId = $documentWithCustomId;
    }

    public function getId(): DocumentPersisterCustomTypedId
    {
        return $this->id;
    }
}
