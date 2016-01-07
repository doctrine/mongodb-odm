<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH389Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testDiscriminatorEmptyEmbeddedDocument()
    {
        //Create root document (with empty embedded document)
        $rootDocument = new RootDocument();

        //Persist root document
        $this->dm->persist($rootDocument);
        $this->dm->flush();
        $this->dm->clear();

        //Get root document id
        $rootDocumentId = $rootDocument->getId();
        unset($rootDocument);

        //Get root document
        $rootDocument = $this->dm->getRepository(__NAMESPACE__ . '\RootDocument')->find($rootDocumentId);

        //Test
        $this->assertInstanceOf(__NAMESPACE__ . '\EmptyEmbeddedDocument', $rootDocument->getEmptyEmbeddedDocument());
    }
}

/** @ODM\Document */
class RootDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\EmbedOne(targetDocument="EmptyMappedSuperClass") */
    protected $emptyEmbeddedDocument;

    public function __construct()
    {
        $this->emptyEmbeddedDocument = new EmptyEmbeddedDocument();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmptyEmbeddedDocument()
    {
        return $this->emptyEmbeddedDocument;
    }
}

/**
 * @ODM\MappedSuperClass
 * @ODM\DiscriminatorField(fieldName="foobar")
 * @ODM\DiscriminatorMap({
 *     "empty"="EmptyEmbeddedDocument"
 * })
 */
class EmptyMappedSuperClass
{
}

/** @ODM\EmbeddedDocument */
class EmptyEmbeddedDocument extends EmptyMappedSuperClass
{
}
