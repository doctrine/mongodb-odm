<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'benchmark_rich_documents')]
class RichDocument
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    public string $title;

    #[ODM\Field(type: 'int')]
    public int $score = 0;

    #[ODM\EmbedOne(targetDocument: Address::class)]
    public ?Address $address = null;

    /** @var Collection<int, Tag> */
    #[ODM\EmbedMany(targetDocument: Tag::class)]
    public Collection $tags;

    /** @var Collection<int, Shape> */
    #[ODM\EmbedMany(targetDocument: Shape::class)]
    public Collection $shapes;

    #[ODM\ReferenceOne(targetDocument: Department::class, storeAs: 'id')]
    public ?Department $department = null;

    /** @var Collection<int, Team> */
    #[ODM\ReferenceMany(targetDocument: Team::class, storeAs: 'id')]
    public Collection $teams;

    public function __construct()
    {
        $this->tags   = new ArrayCollection();
        $this->shapes = new ArrayCollection();
        $this->teams  = new ArrayCollection();
    }
}
