<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class IdentifiedChapter
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, Page> */
    #[ODM\EmbedMany(targetDocument: Page::class)]
    public $pages;

    public function __construct(?string $name = null)
    {
        $this->name  = $name;
        $this->pages = new ArrayCollection();
    }
}
