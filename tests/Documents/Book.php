<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Book
{
    public const CLASSNAME = self::class;

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="int")
     * @ODM\Version
     *
     * @var int|null
     */
    public $version = 1;

    /**
     * @ODM\EmbedMany(targetDocument=Chapter::class, strategy="atomicSet")
     *
     * @var Collection<int, Chapter>
     */
    public $chapters;

    /**
     * @ODM\EmbedMany(targetDocument=IdentifiedChapter::class, strategy="atomicSet")
     *
     * @var Collection<int, IdentifiedChapter>
     */
    public $identifiedChapters;

    public function __construct()
    {
        $this->chapters           = new ArrayCollection();
        $this->identifiedChapters = new ArrayCollection();
    }
}
