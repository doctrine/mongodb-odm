<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH2754Test extends BaseTestCase
{
    public function testSiblingCollectionsWithSharedPrefixArePersisted(): void
    {
        $document           = new GH2754Document();
        $document->embedded = new GH2754Embedded();
        $document->embedded->codes->add(new GH2754Item('a'));
        $document->embedded->codesV2->add(new GH2754Item('b'));
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH2754Document::class, $document->id);
        self::assertNotNull($document);

        // Replace both sibling collections within the same flush. The field name "codes"
        // is a prefix of "codesV2", which previously caused the latter to be dropped.
        $document->embedded->codes   = new ArrayCollection([new GH2754Item('c')]);
        $document->embedded->codesV2 = new ArrayCollection([new GH2754Item('d'), new GH2754Item('e')]);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH2754Document::class, $document->id);
        self::assertNotNull($document);
        self::assertCount(1, $document->embedded->codes);
        self::assertCount(2, $document->embedded->codesV2, 'Sibling collection with prefixed name must be persisted');
        self::assertSame('d', $document->embedded->codesV2[0]->name);
        self::assertSame('e', $document->embedded->codesV2[1]->name);
    }
}

#[ODM\Document]
class GH2754Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    #[ODM\EmbedOne(targetDocument: GH2754Embedded::class)]
    public ?GH2754Embedded $embedded = null;
}

#[ODM\EmbeddedDocument]
class GH2754Embedded
{
    /** @var Collection<int, GH2754Item> */
    #[ODM\EmbedMany(targetDocument: GH2754Item::class)]
    public Collection $codes;

    /** @var Collection<int, GH2754Item> */
    #[ODM\EmbedMany(targetDocument: GH2754Item::class)]
    public Collection $codesV2;

    public function __construct()
    {
        $this->codes   = new ArrayCollection();
        $this->codesV2 = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class GH2754Item
{
    #[ODM\Field(type: 'string')]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
