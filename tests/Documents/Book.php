<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Book
{
    public const CLASSNAME = self::class;

    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var int|null */
    #[ODM\Field(type: 'int')]
    #[ODM\Version]
    public $version = 1;

    /** @var Collection<int, Chapter> */
    #[ODM\EmbedMany(targetDocument: Chapter::class, strategy: 'atomicSet')]
    public $chapters;

    /** @var Collection<int, IdentifiedChapter> */
    #[ODM\EmbedMany(targetDocument: IdentifiedChapter::class, strategy: 'atomicSet')]
    public $identifiedChapters;

    public function __construct()
    {
        $this->chapters           = new ArrayCollection();
        $this->identifiedChapters = new ArrayCollection();
    }
}
