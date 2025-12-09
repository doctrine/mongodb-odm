<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class BrowseNode
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var BrowseNode|null */
    #[ODM\ReferenceOne(targetDocument: self::class, inversedBy: 'children', cascade: ['all'])]
    public $parent;

    /** @var Collection<int, BrowseNode> */
    #[ODM\ReferenceMany(targetDocument: self::class, mappedBy: 'parent', cascade: ['all'])]
    public $children;

    public function __construct(?string $name = null)
    {
        $this->name     = $name;
        $this->children = new ArrayCollection();
    }

    public function addChild(BrowseNode $child): void
    {
        $child->parent    = $this;
        $this->children[] = $child;
    }
}
