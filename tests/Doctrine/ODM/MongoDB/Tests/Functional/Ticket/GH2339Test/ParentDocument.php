<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH2339Test;

use Doctrine\ODM\MongoDB\Tests\Functional\Ticket\EmbeddedDocument;
use MongoDB\BSON\ObjectIdInterface;

/**
 * @ODM\Document
 */
class ParentDocument
{
    /**
     * @ODM\Id
     */
    protected ObjectIdInterface $id;

    /**
     * @var EmbeddedDocument[]
     *
     * @ODM\EmbedMany(targetDocument=EmbeddedDocument::class)
     */
    protected array $embedded = [];

    public function getId(): ObjectIdInterface
    {
        return $this->id;
    }

    public function addEmbedded(EmbeddedDocument $document)
    {
        $this->embedded[] = $document;
    }

    public function getEmbedded(): array
    {
        return $this->getEmbedded();
    }
}
