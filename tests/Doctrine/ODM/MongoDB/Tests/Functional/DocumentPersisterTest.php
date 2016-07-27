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

        $this->class = DocumentPersisterTestDocument::class;

        $collection = $this->dm->getDocumentCollection($this->class);
        $collection->drop();

        foreach (array('a', 'b', 'c', 'd') as $name) {
            $document = array('dbName' => $name);
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

        $updatedData = $this->dm->getDocumentCollection($this->class)->findOne(array('_id' => $originalData['_id']));

        $this->assertEquals($originalData, $updatedData);
    }

    public function testExistsReturnsTrueForExistentDocuments()
    {
        foreach (array('a', 'b', 'c', 'd') as $name) {
            $document = $this->documentPersister->load(array('name' => $name));
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
        $criteria = array('name' => array('$in' => array('a', 'b')));
        $sort = array('name' => -1);

        $document = $this->documentPersister->load($criteria, null, array(), 0, $sort);

        $this->assertInstanceOf($this->class, $document);
        $this->assertEquals('b', $document->name);
    }

    public function testLoadAllPreparesCriteriaAndSort()
    {
        $criteria = array('name' => array('$in' => array('a', 'b')));
        $sort = array('name' => -1);

        $cursor = $this->documentPersister->loadAll($criteria, $sort);
        $documents = iterator_to_array($cursor, false);

        $this->assertInstanceOf($this->class, $documents[0]);
        $this->assertEquals('b', $documents[0]->name);
        $this->assertInstanceOf($this->class, $documents[1]);
        $this->assertEquals('a', $documents[1]->name);
    }

    public function testLoadAllWithSortLimitAndSkip()
    {
        $sort = array('name' => -1);

        $cursor = $this->documentPersister->loadAll(array(), $sort, 1, 2);
        $documents = iterator_to_array($cursor, false);

        $this->assertInstanceOf($this->class, $documents[0]);
        $this->assertEquals('b', $documents[0]->name);
        $this->assertCount(1, $documents);
    }

    public function testLoadAllWithSortLimitAndSkipAndRecreatedCursor()
    {
        $sort = array('name' => -1);

        $cursor = $this->documentPersister->loadAll(array(), $sort, 1, 2);

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
        return array(
            array('name', 'dbName'),
            array('association', 'associationName'),
            array('association.id', 'associationName._id'),
            array('association.nested', 'associationName.nestedName'),
            array('association.nested.$id', 'associationName.nestedName.$id'),
            array('association.nested._id', 'associationName.nestedName._id'),
            array('association.nested.id', 'associationName.nestedName._id'),
            array('association.nested.association.nested.$id', 'associationName.nestedName.associationName.nestedName.$id'),
            array('association.nested.association.nested.id', 'associationName.nestedName.associationName.nestedName._id'),
            array('association.nested.association.nested.firstName', 'associationName.nestedName.associationName.nestedName.firstName'),
        );
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithHashId($hashId)
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = array('_id' => $hashId);
        $expected = array('_id' => (object) $hashId);

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithHashIdAndInOperators($hashId)
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = array('_id' => array('$exists' => true));
        $expected = array('_id' => array('$exists' => true));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('_id' => array('$elemMatch' => $hashId));
        $expected = array('_id' => array('$elemMatch' => (object) $hashId));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('_id' => array('$in' => array($hashId)));
        $expected = array('_id' => array('$in' => array((object) $hashId)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('_id' => array('$not' => array('$elemMatch' => $hashId)));
        $expected = array('_id' => array('$not' => array('$elemMatch' => (object) $hashId)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('_id' => array('$not' => array('$in' => array($hashId))));
        $expected = array('_id' => array('$not' => array('$in' => array((object) $hashId))));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    public function provideHashIdentifiers()
    {
        return array(
            array(array('key' => 'value')),
            array(array(0 => 'first', 1 => 'second')),
            array(array('$ref' => 'ref', '$id' => 'id')),
        );
    }

    public function testPrepareQueryOrNewObjWithSimpleReferenceToTargetDocumentWithNormalIdType()
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new \MongoId();

        $value = array('simpleRef' => (string) $id);
        $expected = array('simpleRef' => $id);

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$exists' => true));
        $expected = array('simpleRef' => array('$exists' => true));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$elemMatch' => (string) $id));
        $expected = array('simpleRef' => array('$elemMatch' => $id));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$in' => array((string) $id)));
        $expected = array('simpleRef' => array('$in' => array($id)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$not' => array('$elemMatch' => (string) $id)));
        $expected = array('simpleRef' => array('$not' => array('$elemMatch' => $id)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$not' => array('$in' => array((string) $id))));
        $expected = array('simpleRef' => array('$not' => array('$in' => array($id))));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithSimpleReferenceToTargetDocumentWithHashIdType($hashId)
    {
        $class = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = array('simpleRef' => $hashId);
        $expected = array('simpleRef' => (object) $hashId);

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$exists' => true));
        $expected = array('simpleRef' => array('$exists' => true));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$elemMatch' => $hashId));
        $expected = array('simpleRef' => array('$elemMatch' => (object) $hashId));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$in' => array($hashId)));
        $expected = array('simpleRef' => array('$in' => array((object) $hashId)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$not' => array('$elemMatch' => $hashId)));
        $expected = array('simpleRef' => array('$not' => array('$elemMatch' => (object) $hashId)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('simpleRef' => array('$not' => array('$in' => array($hashId))));
        $expected = array('simpleRef' => array('$not' => array('$in' => array((object) $hashId))));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    public function testPrepareQueryOrNewObjWithDBRefReferenceToTargetDocumentWithNormalIdType()
    {
        $class = DocumentPersisterTestHashIdDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $id = new \MongoId();

        $value = array('complexRef.id' => (string) $id);
        $expected = array('complexRef.$id' => $id);

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$exists' => true));
        $expected = array('complexRef.$id' => array('$exists' => true));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$elemMatch' => (string) $id));
        $expected = array('complexRef.$id' => array('$elemMatch' => $id));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$in' => array((string) $id)));
        $expected = array('complexRef.$id' => array('$in' => array($id)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$not' => array('$elemMatch' => (string) $id)));
        $expected = array('complexRef.$id' => array('$not' => array('$elemMatch' => $id)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$not' => array('$in' => array((string) $id))));
        $expected = array('complexRef.$id' => array('$not' => array('$in' => array($id))));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @dataProvider provideHashIdentifiers
     */
    public function testPrepareQueryOrNewObjWithDBRefReferenceToTargetDocumentWithHashIdType($hashId)
    {
        $class = DocumentPersisterTestDocument::class;
        $documentPersister = $this->uow->getDocumentPersister($class);

        $value = array('complexRef.id' => $hashId);
        $expected = array('complexRef.$id' => (object) $hashId);

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$exists' => true));
        $expected = array('complexRef.$id' => array('$exists' => true));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$elemMatch' => $hashId));
        $expected = array('complexRef.$id' => array('$elemMatch' => (object) $hashId));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$in' => array($hashId)));
        $expected = array('complexRef.$id' => array('$in' => array((object) $hashId)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$not' => array('$elemMatch' => $hashId)));
        $expected = array('complexRef.$id' => array('$not' => array('$elemMatch' => (object) $hashId)));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));

        $value = array('complexRef.id' => array('$not' => array('$in' => array($hashId))));
        $expected = array('complexRef.$id' => array('$not' => array('$in' => array((object) $hashId))));

        $this->assertEquals($expected, $documentPersister->prepareQueryOrNewObj($value));
    }

    /**
     * @return array
     */
    public static function dataProviderTestWriteConcern()
    {
        return array(
            'default' => array(
                'className' => DocumentPersisterTestDocument::class,
                'writeConcern' => 1,
            ),
            'acknowledged' => array(
                'className' => DocumentPersisterWriteConcernAcknowledged::class,
                'writeConcern' => 1,
            ),
            'unacknowledged' => array(
                'className' => DocumentPersisterWriteConcernUnacknowledged::class,
                'writeConcern' => 0,
            ),
            'majority' => array(
                'className' => DocumentPersisterWriteConcernMajority::class,
                'writeConcern' => 'majority',
            ),
        );
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

        $collection = $this->createMock('\MongoCollection');
        $collection->expects($this->any())
            ->method('batchInsert')
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

        $collection = $this->createMock('\MongoCollection');
        $collection->expects($this->any())
            ->method('update')
            ->with($this->isType('array'), $this->isType('array'), $this->logicalAnd($this->arrayHasKey('w'), $this->contains($writeConcern)));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $testDocument = new $class();
        $testDocument->id = new \MongoId();
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

        $collection = $this->createMock('\MongoCollection');
        $collection->expects($this->once())
            ->method('remove')
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

        $collection = $this->createMock('\MongoCollection');
        $collection->expects($this->any())
            ->method('batchInsert')
            ->with($this->isType('array'), $this->equalTo(array('w' => 0)));

        $reflectionProperty = new \ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $this->dm->getConfiguration()->setDefaultCommitOptions(array('w' => 0));

        $testDocument = new $class();
        $this->dm->persist($testDocument);
        $this->dm->flush();
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

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestHashIdDocument", storeAs="id") */
    public $simpleRef;

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestHashIdDocument", storeAs="dbRef") */
    public $semiComplexRef;

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestHashIdDocument", storeAs="dbRefWithDb") */
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

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestDocument", storeAs="id") */
    public $simpleRef;

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestDocument", storeAs="dbRef") */
    public $semiComplexRef;

    /** @ODM\ReferenceOne(targetDocument="DocumentPersisterTestDocument", storeAs="dbRefWithDb") */
    public $complexRef;
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
