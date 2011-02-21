<?php

namespace Documents;

/** @Document */
class BrowseNode
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /**
     * @ReferenceOne(targetDocument="BrowseNode", inversedBy="children", cascade={"all"})
     */
    public $parent;

    /**
     * @ReferenceMany(targetDocument="BrowseNode", mappedBy="parent", cascade={"all"})
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