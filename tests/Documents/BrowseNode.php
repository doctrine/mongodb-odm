<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class BrowseNode
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument="BrowseNode", inversedBy="children", cascade={"all"})
     */
    public $parent;

    /**
     * @ODM\ReferenceMany(targetDocument="BrowseNode", mappedBy="parent", cascade={"all"})
     */
    public $children;

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addChild(BrowseNode $child)
    {
        $child->parent = $this;
        $this->children[] = $child;
    }
}
