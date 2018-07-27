<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;

class DocumentPersisterTest extends BaseTest
{
    private $class;

    /** @var DocumentPersister */
    private $documentPersister;

    public function setUp()
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

    public function testExecuteUpsertShouldNeverReplaceDocuments()
    {
        $originalData = $this->dm->getDocumentCollection($this->class)->findOne();

        $document = new DocumentPersisterTestDocument();
        $document->id = $originalData['_id'];

        $this->dm->persist($document);
        $this->dm->flush();

        $updatedData = $this->dm->getDocumentCollection($this->class)->findOne(['_id' => $originalData['_id']]);

        $this->assertEquals($originalData, $updatedData);
    }

    public function testExistsReturnsTrueForExistentDocuments()
    {
        foreach (['a', 'b', 'c', 'd'] as $name) {
            $document = $this->documentPersister->load(['name' => $name]);
            $this->assertTrue($this->documentPersister->exists($document));
        }
    }

    public function testExistsReturnsFalseForNonexistentDocuments()
    {
        $document = new DocumentPersisterTestDocument();
        $document->id = new ObjectId();

        $this->assertFalse($this->documentPersister->exists($document));
    }

    public function testLoadPreparesCriteriaAndSort()
    {
        $criteria = ['name' => ['$in' => ['a', 'b']]];
        $sort = ['name' => -1];

        $document = $this->documentPersister->load($criteria, null, [], 0, $sort);

        $this->assertInstanceOf($this->class, $document);
        $this->assertEquals('b', $document->name);
    }

    public function testLoadAllPreparesCriteriaAndSort()
    {
        $criteria = ['name' => ['$in' => ['a', 'b']]];
        $sort = ['name' => -1];

        $cursor = $this->documentPersister->loadAll($criteria, $sort);
        $documents = $cursor->toArray();

        $this->assertInstanceOf($this->class, $documents[0]);
        $this->assertEquals('b', $documents[0]->name);
        $this->assertInstanceOf($this->class, $documents[1]);
        $this->assertEquals('a', $documents[1]->name);
    }

    public function testLoadAllWithSortLimitAndSkip()
    {
        $sort = ['name' => -1];

        $cursor = $this->documentPersister->loadAll([], $sort, 1, 2);
        $documents = $cursor->toArray();

        $this->assertInstanceOf($this->class, $documents[0]);
        $this->assertEquals('b', $documents[0]->name);
        $this->assertCount(1, $documents);
    }

    /**
     * @dataProvider getTestPrepareFieldNameData
     */
    public function testPrepareFieldName($fieldName, $expected)
    {
        $this->assertEquals($expected, $this->documentPersister->prepareFieldName($fieldName));
    }

    public function getTestPrepareFieldNameData()
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

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithHashId($hashId)
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = ['_id' => $hashId];
        $expected = ['_id' => (object) $hashId];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithHashIdAndInOperators($hashId)
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = ['_id' => ['$exists' => true]];
        $expected = ['_id' => ['$exists' => true]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['_id' => ['$elemMatch' => $hashId]];
        $expected = ['_id' => ['$elemMatch' => (object) $hashId]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['_id' => ['$in' => [$hashId]]];
        $expected = ['_id' => ['$in' => [(object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['_id' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['_id' => ['$not' => ['$elemMatch' => (object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['_id' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['_id' => ['$not' => ['$in' => [(object) $hashId]]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    public function provideHashIdentifiers()
    {
        return [
            [['key' => 'value']],
            [[0 => 'first', 1 => 'second']],
            [['$ref' => 'ref', '$id' => 'id']],
        ];
    }

    public function testPrepareQueryOrNewObjWithSimpleReferenceToTargetDocumentWithNormalIdType()
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new ObjectId();

        $value = ['simpleRef' => (string) $id];
        $expected = ['simpleRef' => $id];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$exists' => true]];
        $expected = ['simpleRef' => ['$exists' => true]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$elemMatch' => (string) $id]];
        $expected = ['simpleRef' => ['$elemMatch' => $id]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$in' => [(string) $id]]];
        $expected = ['simpleRef' => ['$in' => [$id]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$not' => ['$elemMatch' => (string) $id]]];
        $expected = ['simpleRef' => ['$not' => ['$elemMatch' => $id]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$not' => ['$in' => [(string) $id]]]];
        $expected = ['simpleRef' => ['$not' => ['$in' => [$id]]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithSimpleReferenceToTargetDocumentWithHashIdType($hashId)
    {
        $class = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = ['simpleRef' => $hashId];
        $expected = ['simpleRef' => (object) $hashId];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$exists' => true]];
        $expected = ['simpleRef' => ['$exists' => true]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$elemMatch' => $hashId]];
        $expected = ['simpleRef' => ['$elemMatch' => (object) $hashId]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$in' => [$hashId]]];
        $expected = ['simpleRef' => ['$in' => [(object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['simpleRef' => ['$not' => ['$elemMatch' => (object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['simpleRef' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['simpleRef' => ['$not' => ['$in' => [(object) $hashId]]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    public function testPrepareQueryOrNewObjWithDBRefReferenceToTargetDocumentWithNormalIdType()
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new ObjectId();

        $value = ['complexRef.id' => (string) $id];
        $expected = ['complexRef.$id' => $id];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$exists' => true]];
        $expected = ['complexRef.$id' => ['$exists' => true]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$elemMatch' => (string) $id]];
        $expected = ['complexRef.$id' => ['$elemMatch' => $id]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$in' => [(string) $id]]];
        $expected = ['complexRef.$id' => ['$in' => [$id]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$not' => ['$elemMatch' => (string) $id]]];
        $expected = ['complexRef.$id' => ['$not' => ['$elemMatch' => $id]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$not' => ['$in' => [(string) $id]]]];
        $expected = ['complexRef.$id' => ['$not' => ['$in' => [$id]]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithDBRefReferenceToTargetDocumentWithHashIdType($hashId)
    {
        $class = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = ['complexRef.id' => $hashId];
        $expected = ['complexRef.$id' => (object) $hashId];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$exists' => true]];
        $expected = ['complexRef.$id' => ['$exists' => true]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$elemMatch' => $hashId]];
        $expected = ['complexRef.$id' => ['$elemMatch' => (object) $hashId]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$in' => [$hashId]]];
        $expected = ['complexRef.$id' => ['$in' => [(object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['complexRef.$id' => ['$not' => ['$elemMatch' => (object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['complexRef.id' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['complexRef.$id' => ['$not' => ['$in' => [(object) $hashId]]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    public function testPrepareQueryOrNewObjWithEmbeddedReferenceToTargetDocumentWithNormalIdType()
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new ObjectId();

        $value = ['embeddedRef.id' => (string) $id];
        $expected = ['embeddedRef.id' => $id];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$exists' => true]];
        $expected = ['embeddedRef.id' => ['$exists' => true]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$elemMatch' => (string) $id]];
        $expected = ['embeddedRef.id' => ['$elemMatch' => $id]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$in' => [(string) $id]]];
        $expected = ['embeddedRef.id' => ['$in' => [$id]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$not' => ['$elemMatch' => (string) $id]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$elemMatch' => $id]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$not' => ['$in' => [(string) $id]]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$in' => [$id]]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithEmbeddedReferenceToTargetDocumentWithHashIdType($hashId)
    {
        $class = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = ['embeddedRef.id' => $hashId];
        $expected = ['embeddedRef.id' => (object) $hashId];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$exists' => true]];
        $expected = ['embeddedRef.id' => ['$exists' => true]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$elemMatch' => $hashId]];
        $expected = ['embeddedRef.id' => ['$elemMatch' => (object) $hashId]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$in' => [$hashId]]];
        $expected = ['embeddedRef.id' => ['$in' => [(object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$not' => ['$elemMatch' => $hashId]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$elemMatch' => (object) $hashId]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = ['embeddedRef.id' => ['$not' => ['$in' => [$hashId]]]];
        $expected = ['embeddedRef.id' => ['$not' => ['$in' => [(object) $hashId]]]];

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @return array
     */
    public static function dataProviderTestWriteConcern()
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
     * @dataProvider dataProviderTestWriteConcern
     *
     * @param string $class
     * @param string $writeConcern
     */
    public function testExecuteInsertsRespectsWriteConcern($class, $writeConcern)
    {
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('insertMany')
            ->with($this->isType('array'), $this->logicalAnd($this->arrayHasKey('w'), $this->contains($writeConcern)));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();
    }

    /**
     * @dataProvider dataProviderTestWriteConcern
     *
     * @param string $class
     * @param string $writeConcern
     */
    public function testExecuteUpsertsRespectsWriteConcern($class, $writeConcern)
    {
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('updateOne')
            ->with($this->isType('array'), $this->isType('array'), $this->logicalAnd($this->arrayHasKey('w'), $this->contains($writeConcern)));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $testDocument->id = new ObjectId();
        $this->dm->persist($testDocument);
        $this->dm->flush();
    }

    /**
     * @dataProvider dataProviderTestWriteConcern
     *
     * @param string $class
     * @param string $writeConcern
     */
    public function testRemoveRespectsWriteConcern($class, $writeConcern)
    {
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('deleteOne')
            ->with($this->isType('array'), $this->logicalAnd($this->arrayHasKey('w'), $this->contains($writeConcern)));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();

        $this->dm->remove($testDocument);
        $this->dm->flush();
    }

    public function testDefaultWriteConcernIsRespected()
    {
        $class = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('insertMany')
            ->with($this->isType('array'), $this->equalTo(['w' => 0]));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $this->dm->getConfiguration()->setDefaultCommitOptions(['w' => 0]);

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();
    }

    public function testVersionIncrementOnUpdateSuccess()
    {
        $this->markTestSkipped('Mocking results to update calls is no longer possible. Rewrite test to not rely on mocking');

        $class = DocumentPersisterTestDocumentWithVersion::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())
            ->method('updateOne')
            ->will($this->returnValue(['n' => 1]));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $testDocument->id = 12345;
        $this->uow->registerManaged($testDocument, 12345, ['id' => 12345]);
        $testDocument->name = 'test';
        $this->dm->persist($testDocument);
        $this->dm->flush();

        $this->assertEquals(2, $testDocument->revision);
    }

    public function testNoVersionIncrementOnUpdateFailure()
    {
        $this->markTestSkipped('Mocking results to update calls is no longer possible. Rewrite test to not rely on mocking');

        $class = DocumentPersisterTestDocumentWithVersion::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())
            ->method('updateOne')
            ->will($this->returnValue(['n' => 0]));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $testDocument->id = 12345;
        $this->uow->registerManaged($testDocument, 12345, ['id' => 12345]);
        $this->expectException(LockException::class);
        $testDocument->name = 'test';
        $this->dm->persist($testDocument);
        $this->dm->flush();

        $this->assertEquals(1, $testDocument->revision);
    }
}

/** @ODM\Document */
class DocumentPersisterTestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(name="dbName", type="string") */
    public $name;

    /**
     * @ODM\EmbedOne(
     *     targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\AbstractDocumentPersisterTestDocumentAssociation",
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "reference"="Doctrine\ODM\MongoDB\Tests\Functional\DocumentPersisterTestDocumentReference",
     *         "embed"="Doctrine\ODM\MongoDB\Tests\Functional\DocumentPersisterTestDocumentEmbed"
     *     },
     *     name="associationName"
     * )
     */
    public $association;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="id") */
    public $simpleRef;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="dbRef") */
    public $semiComplexRef;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="dbRefWithDb") */
    public $complexRef;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestHashIdDocument::class, storeAs="ref") */
    public $embeddedRef;
}

/** @ODM\Document */
class DocumentPersisterTestDocumentWithVersion
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(name="dbName", type="string") */
    public $name;

    /** @ODM\Version @ODM\Field(type="int") */
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
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(name="nestedName") */
    public $nested;

    /**
     * @ODM\EmbedOne(
     *     targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\AbstractDocumentPersisterTestDocumentAssociation",
     *     discriminatorField="type",
     *     discriminatorMap={
     *         "reference"="Doctrine\ODM\MongoDB\Tests\Functional\DocumentPersisterTestDocumentReference",
     *         "embed"="Doctrine\ODM\MongoDB\Tests\Functional\DocumentPersisterTestDocumentEmbed"
     *     },
     *     name="associationName"
     * )
     */
    public $association;
}

/** @ODM\EmbeddedDocument */
class DocumentPersisterTestDocumentReference extends AbstractDocumentPersisterTestDocumentAssociation
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(name="nestedName") */
    public $nested;
}

/** @ODM\EmbeddedDocument */
class DocumentPersisterTestDocumentEmbed extends AbstractDocumentPersisterTestDocumentAssociation
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(name="nestedName") */
    public $nested;
}

/** @ODM\Document */
class DocumentPersisterTestHashIdDocument
{
    /** @ODM\Id(strategy="none", options={"type"="hash"}) */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="id") */
    public $simpleRef;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="dbRef") */
    public $semiComplexRef;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="dbRefWithDb") */
    public $complexRef;

    /** @ODM\ReferenceOne(targetDocument=DocumentPersisterTestDocument::class, storeAs="ref") */
    public $embeddedRef;
}

/** @ODM\Document(writeConcern="majority") */
class DocumentPersisterWriteConcernMajority
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\Document(writeConcern=0) */
class DocumentPersisterWriteConcernUnacknowledged
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\Document(writeConcern=1) */
class DocumentPersisterWriteConcernAcknowledged
{
    /** @ODM\Id */
    public $id;
}
