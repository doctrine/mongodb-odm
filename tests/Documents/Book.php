<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Book
{
    public const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="int") @ODM\Version */
    public $version = 1;

    /** @ODM\EmbedMany(targetDocument=Chapter::class, strategy="atomicSet") */
    public $chapters;

    /** @ODM\EmbedMany(targetDocument=IdentifiedChapter::class, strategy="atomicSet") */
    public $identifiedChapters;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
        $this->identifiedChapters = new ArrayCollection();
    }
}
