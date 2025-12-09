<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH389Test extends BaseTestCase
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
        self::assertInstanceOf(EmptyEmbeddedDocument::class, $rootDocument->getEmptyEmbeddedDocument());
    }
}

#[ODM\Document]
class RootDocument
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var EmptyEmbeddedDocument */
    #[ODM\EmbedOne(targetDocument: EmptyMappedSuperClass::class)]
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

#[ODM\MappedSuperclass]
#[ODM\DiscriminatorField('foobar')]
#[ODM\DiscriminatorMap(['empty' => EmptyEmbeddedDocument::class])]
class EmptyMappedSuperClass
{
}

#[ODM\EmbeddedDocument]
class EmptyEmbeddedDocument extends EmptyMappedSuperClass
{
}
