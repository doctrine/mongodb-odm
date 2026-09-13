<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Mirrors the shape of the "large_doc_nested" fixture from the MongoDB
 * driver benchmark corpus: a document with several named embedded
 * sub-documents plus an embedded array, to exercise deeper association
 * mapping and recursive hydration.
 */
#[ODM\Document(collection: 'benchmark_nested_documents')]
class NestedDocument
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\EmbedOne(targetDocument: NestedStrItem::class)]
    public ?NestedStrItem $embeddedStrDoc1 = null;

    #[ODM\EmbedOne(targetDocument: NestedStrItem::class)]
    public ?NestedStrItem $embeddedStrDoc2 = null;

    #[ODM\EmbedOne(targetDocument: NestedStrItem::class)]
    public ?NestedStrItem $embeddedStrDoc3 = null;

    #[ODM\EmbedOne(targetDocument: NestedStrItem::class)]
    public ?NestedStrItem $embeddedStrDoc4 = null;

    #[ODM\EmbedOne(targetDocument: NestedStrItem::class)]
    public ?NestedStrItem $embeddedStrDoc5 = null;

    /** @var Collection<int, NestedStrItem> */
    #[ODM\EmbedMany(targetDocument: NestedStrItem::class)]
    public Collection $embeddedStrDocArray;

    #[ODM\EmbedOne(targetDocument: NestedIntItem::class)]
    public ?NestedIntItem $embeddedIntDoc8 = null;

    #[ODM\EmbedOne(targetDocument: NestedIntItem::class)]
    public ?NestedIntItem $embeddedIntDoc9 = null;

    #[ODM\EmbedOne(targetDocument: NestedIntItem::class)]
    public ?NestedIntItem $embeddedIntDoc10 = null;

    #[ODM\EmbedOne(targetDocument: NestedIntItem::class)]
    public ?NestedIntItem $embeddedIntDoc11 = null;

    #[ODM\EmbedOne(targetDocument: NestedIntItem::class)]
    public ?NestedIntItem $embeddedIntDoc12 = null;

    #[ODM\EmbedOne(targetDocument: NestedIntItem::class)]
    public ?NestedIntItem $embeddedIntDoc13 = null;

    #[ODM\EmbedOne(targetDocument: NestedIntItem::class)]
    public ?NestedIntItem $embeddedIntDoc14 = null;

    public function __construct()
    {
        $this->embeddedStrDocArray = new ArrayCollection();
    }
}
