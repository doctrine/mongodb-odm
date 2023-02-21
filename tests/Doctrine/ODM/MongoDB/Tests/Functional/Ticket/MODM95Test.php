<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM95Test extends BaseTest
{
    public function testDocumentWithEmbeddedDocuments(): void
    {
        $embeddedDocuments = [new MODM95TestEmbeddedDocument('foo')];

        $testDoc = new MODM95TestDocument();
        $testDoc->setEmbeddedDocuments($embeddedDocuments);
        $this->dm->persist($testDoc);
        $this->dm->flush();
        $this->dm->clear();

        $testDoc = $this->dm->find(MODM95TestDocument::class, $testDoc->id);

        self::assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());

        $this->dm->remove($testDoc);
        $this->dm->flush();
        $this->dm->clear();

        $testDocLoad = $this->dm->find(MODM95TestDocument::class, $testDoc->id);
        self::assertNull($testDocLoad);

        $this->dm->persist($testDoc);
        $this->dm->flush();
        $this->dm->clear();

        $testDocLoad = $this->dm->find(MODM95TestDocument::class, $testDoc->id);
        self::assertNotNull($testDocLoad);

        self::assertEquals($embeddedDocuments, $testDocLoad->embeddedDocuments->toArray());
    }
}

/** @ODM\Document */
class MODM95TestDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    // Note: Test case fails with default "pushAll" strategy, but "set" works
    /**
     * @ODM\EmbedMany(targetDocument=MODM95TestEmbeddedDocument::class)
     *
     * @var Collection<int, MODM95TestEmbeddedDocument>
     */
    public $embeddedDocuments;

    public function __construct()
    {
        $this->embeddedDocuments = new ArrayCollection();
    }

    /** @param iterable<MODM95TestEmbeddedDocument> $embeddedDocuments */
    public function setEmbeddedDocuments(iterable $embeddedDocuments): void
    {
        $this->embeddedDocuments->clear();

        foreach ($embeddedDocuments as $embeddedDocument) {
            $this->embeddedDocuments->add($embeddedDocument);
        }
    }
}

/** @ODM\EmbeddedDocument */
class MODM95TestEmbeddedDocument
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
