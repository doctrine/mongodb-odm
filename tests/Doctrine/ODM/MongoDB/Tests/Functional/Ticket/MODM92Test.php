<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Traversable;

use function is_iterable;

class MODM92Test extends BaseTest
{
    public function testDocumentWithEmbeddedDocuments(): void
    {
        $embeddedDocuments = [new MODM92TestEmbeddedDocument('foo')];

        $testDoc = new MODM92TestDocument();
        $testDoc->setEmbeddedDocuments($embeddedDocuments);
        $this->dm->persist($testDoc);
        $this->dm->flush();
        $this->dm->clear();

        $testDoc = $this->dm->find(MODM92TestDocument::class, $testDoc->id);
        self::assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());

        $embeddedDocuments = [new MODM92TestEmbeddedDocument('bar')];

        $testDoc->setEmbeddedDocuments($embeddedDocuments);
        self::assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());

        $this->dm->flush();
        $this->dm->clear();
        $testDoc = $this->dm->find(MODM92TestDocument::class, $testDoc->id);

        self::assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());
    }
}

/** @ODM\Document */
class MODM92TestDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    // Note: Test case fails with default "pushAll" strategy, but "set" works
    /**
     * @ODM\EmbedMany(targetDocument=MODM92TestEmbeddedDocument::class)
     *
     * @var Collection<int, MODM92TestEmbeddedDocument>
     */
    public $embeddedDocuments;

    public function __construct()
    {
        $this->embeddedDocuments = new ArrayCollection();
    }

    /**
     * Sets children
     *
     * If $images is not an array or Traversable object, this method will simply
     * clear the images collection property.  If any elements in the parameter
     * are not an Image object, this method will attempt to convert them to one
     * by mapping array indexes (size URL's are required, cropMetadata is not).
     * Any invalid elements will be ignored.
     *
     * @param array<MODM92TestEmbeddedDocument>|Traversable<MODM92TestEmbeddedDocument> $embeddedDocuments
     */
    public function setEmbeddedDocuments($embeddedDocuments): void
    {
        $this->embeddedDocuments->clear();

        if (! is_iterable($embeddedDocuments)) {
            return;
        }

        foreach ($embeddedDocuments as $embeddedDocument) {
            $this->embeddedDocuments->add($embeddedDocument);
        }
    }
}

/** @ODM\EmbeddedDocument */
class MODM92TestEmbeddedDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
