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
     * @ODM\EmbedMany(targetDocument=InlinedDocument::class)
     *
     * @var InlinedDocument[]
     */
    protected array $inlined = [];

    public function getId(): ObjectIdInterface
    {
        return $this->id;
    }

    public function addInlined(InlinedDocument $document)
    {
        $this->inlined[] = $document;
    }

    public function getInlined(): array
    {
        return $this->inlined;
    }
}
