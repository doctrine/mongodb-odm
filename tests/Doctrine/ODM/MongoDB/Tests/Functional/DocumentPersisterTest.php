<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class DocumentPersisterTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $class;
    private $documentPersister;

    public function setUp()
    {
        parent::setUp();

        $this->class = __NAMESPACE__ . '\DocumentPersisterTestDocument';

        $collection = $this->dm->getDocumentCollection($this->class);
        $collection->drop();

        foreach (['a', 'b', 'c', 'd'] as $name) {
            $document = ['dbName' => $name];
            $collection->insert($document);
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
        $document->id = new \MongoId();

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
        $documents = iterator_to_array($cursor, false);

        $this->assertInstanceOf($this->class, $documents[0]);
        $this->assertEquals('b', $documents[0]->name);
        $this->assertInstanceOf($this->class, $documents[1]);
        $this->assertEquals('a', $documents[1]->name);
    }

    public function testLoadAllWithSortLimitAndSkip()
    {
        $sort = ['name' => -1];

        $cursor = $this->documentPersister->loadAll([], $sort, 1, 2);
        $documents = iterator_to_array($cursor, false);

        $this->assertInstanceOf($this->class, $documents[0]);
        $this->assertEquals('b', $documents[0]->name);
        $this->assertCount(1, $documents);
    }

    public function testLoadAllWithSortLimitAndSkipAndRecreatedCursor()
    {
        $sort = ['name' => -1];

        $cursor = $this->documentPersister->loadAll([], $sort, 1, 2);

        $cursor = clone $cursor;
        $cursor->recreate();

        $documents = iterator_to_array($cursor, false);

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
        $class = __NAMESPACE__ . '\DocumentPersisterTestHashIdDocument';
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
        $class = __NAMESPACE__ . '\DocumentPersisterTestHashIdDocument';
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
        $class = __NAMESPACE__ . '\DocumentPersisterTestHashIdDocument';
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new \MongoId();

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
        $class = __NAMESPACE__ . '\DocumentPersisterTestDocument';
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
        $class = __NAMESPACE__ . '\DocumentPersisterTestHashIdDocument';
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new \MongoId();

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
        $class = __NAMESPACE__ . '\DocumentPersisterTestDocument';
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
}

/** @ODM\Document */
class DocumentPersisterTestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String(name="dbName") */
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

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestHashIdDocument", simple=true) */
    public $simpleRef;

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestHashIdDocument") */
    public $complexRef;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
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

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestDocument", simple=true) */
    public $simpleRef;

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestDocument") */
    public $complexRef;
}
