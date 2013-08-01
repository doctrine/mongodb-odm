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

        foreach (array('a', 'b', 'c', 'd') as $name) {
            $document = array('dbName' => $name);
            $collection->insert($document);
        }

        $this->documentPersister = $this->uow->getDocumentPersister($this->class);
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
     * @dataProvider getTestPrepareQueryOrNewObjData
     */
    public function testPrepareFieldName($fieldName, $expected)
    {
        $this->assertEquals($expected, $this->documentPersister->prepareFieldName($fieldName));
    }

    public function getTestPrepareQueryOrNewObjData()
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
