<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class BrowseNode
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceOne(targetDocument=BrowseNode::class, inversedBy="children", cascade={"all"}) */
    public $parent;

    /** @ODM\ReferenceMany(targetDocument=BrowseNode::class, mappedBy="parent", cascade={"all"}) */
    public $children;

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->children = new ArrayCollection();
    }

    public function addChild(BrowseNode $child)
    {
        $child->parent = $this;
        $this->children[] = $child;
    }
}
