<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function get_class;

class GH1225Test extends BaseTest
{
    public function testRemoveAddEmbeddedDocToExistingDocumentWithPreUpdateHook(): void
    {
        $doc = new GH1225Document();
        $doc->embeds->add(new GH1225EmbeddedDocument('foo'));
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc         = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $embeddedDoc = $doc->embeds->first();
        $doc->embeds->clear();
        $doc->embeds->add($embeddedDoc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        self::assertCount(1, $doc->embeds);
    }
}

/**
 * @ODM\Document
 * @ODM\HasLifecycleCallbacks
 */
class GH1225Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(strategy="atomicSet", targetDocument=GH1225EmbeddedDocument::class)
     *
     * @var Collection<int, GH1225EmbeddedDocument>
     */
    public $embeds;

    public function __construct()
    {
        $this->embeds = new ArrayCollection();
    }

    /** @ODM\PreUpdate */
    public function exampleHook(): void
    {
    }
}

/** @ODM\EmbeddedDocument */
class GH1225EmbeddedDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
