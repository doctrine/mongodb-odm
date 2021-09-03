<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class BrowseNode
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
     * @ODM\ReferenceOne(targetDocument=BrowseNode::class, inversedBy="children", cascade={"all"})
     *
     * @var BrowseNode|null
     */
    public $parent;

    /**
     * @ODM\ReferenceMany(targetDocument=BrowseNode::class, mappedBy="parent", cascade={"all"})
     *
     * @var Collection<int, BrowseNode>
     */
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
