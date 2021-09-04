<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class IdentifiedChapter
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

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

    public function __construct(?string $name = null)
    {
        $this->name  = $name;
        $this->pages = new ArrayCollection();
    }
}
