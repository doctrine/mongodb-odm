<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class TypedDocument
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\EmbedOne(targetDocument: TypedEmbeddedDocument::class)]
    public TypedEmbeddedDocument $embedOne;

    #[ODM\EmbedOne(targetDocument: TypedEmbeddedDocument::class, nullable: true)]
    public ?TypedEmbeddedDocument $nullableEmbedOne;

    #[ODM\EmbedOne(targetDocument: TypedEmbeddedDocument::class, nullable: true)]
    public ?TypedEmbeddedDocument $initializedNullableEmbedOne = null;

    #[ODM\ReferenceOne(targetDocument: self::class)]
    public TypedDocument $referenceOne;

    #[ODM\ReferenceOne(targetDocument: self::class, nullable: true)]
    public ?TypedDocument $nullableReferenceOne;

    #[ODM\ReferenceOne(targetDocument: self::class, nullable: true)]
    public ?TypedDocument $initializedNullableReferenceOne = null;

    /** @var Collection<int, TypedEmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: TypedEmbeddedDocument::class)]
    private Collection $embedMany;

    public function __construct()
    {
        $this->embedMany = new ArrayCollection();
    }

    /** @return Collection<int, TypedEmbeddedDocument> */
    public function getEmbedMany(): Collection
    {
        return $this->embedMany;
    }
}
