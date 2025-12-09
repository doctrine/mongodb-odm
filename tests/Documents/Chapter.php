<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
#[ODM\HasLifecycleCallbacks]
class Chapter
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, Page> */
    #[ODM\EmbedMany(targetDocument: Page::class)]
    public $pages;

    /** @var int */
    #[ODM\Field(type: 'int')]
    public $nbPages = 0;

    public function __construct(?string $name = null)
    {
        $this->name  = $name;
        $this->pages = new ArrayCollection();
    }

    #[ODM\PostUpdate]
    public function doThisAfterAnUpdate(): void
    {
        /* Do not do this at home, it is here only to see if nothing breaks,
         * field will not be updated in database with new value unless another
         * flush() is made.
         */
        $this->nbPages = $this->pages->count();
    }
}
