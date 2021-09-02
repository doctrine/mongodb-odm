<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH389Test extends BaseTest
{
    public function testDiscriminatorEmptyEmbeddedDocument(): void
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
        $rootDocument = $this->dm->getRepository(RootDocument::class)->find($rootDocumentId);

        //Test
        $this->assertInstanceOf(EmptyEmbeddedDocument::class, $rootDocument->getEmptyEmbeddedDocument());
    }
}

/** @ODM\Document */
class RootDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\EmbedOne(targetDocument=EmptyMappedSuperClass::class)
     *
     * @var EmptyEmbeddedDocument
     */
    protected $emptyEmbeddedDocument;

    public function __construct()
    {
        $this->emptyEmbeddedDocument = new EmptyEmbeddedDocument();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmptyEmbeddedDocument(): EmptyEmbeddedDocument
    {
        return $this->emptyEmbeddedDocument;
    }
}

/**
 * @ODM\MappedSuperClass
 * @ODM\DiscriminatorField("foobar")
 * @ODM\DiscriminatorMap({
 *     "empty"=EmptyEmbeddedDocument::class
 * })
 */
class EmptyMappedSuperClass
{
}

/** @ODM\EmbeddedDocument */
class EmptyEmbeddedDocument extends EmptyMappedSuperClass
{
}
