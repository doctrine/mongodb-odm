<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH2339Test;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectIdInterface;

/**
 * @ODM\Document
 */
class ParentDocument
{
    /** @ODM\Id */
    protected ObjectIdInterface $id;

    /**
     * @ODM\EmbedMany(targetDocument=EmbeddedDocument::class)
     *
     * @var EmbeddedDocument[]
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
        return $this->embedded;
    }
}
