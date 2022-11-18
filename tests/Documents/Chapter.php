<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 * @ODM\HasLifecycleCallbacks
 */
class Chapter
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedMany(targetDocument=Page::class)
     *
     * @var Collection<int, Page>
     */
    public $pages;

    /**
     * @ODM\Field(type="int")
     *
     * @var int
     */
    public $nbPages = 0;

    public function __construct(?string $name = null)
    {
        $this->name  = $name;
        $this->pages = new ArrayCollection();
    }

    /** @ODM\PostUpdate */
    public function doThisAfterAnUpdate(): void
    {
        /* Do not do this at home, it is here only to see if nothing breaks,
         * field will not be updated in database with new value unless another
         * flush() is made.
         */
        $this->nbPages = $this->pages->count();
    }
}
