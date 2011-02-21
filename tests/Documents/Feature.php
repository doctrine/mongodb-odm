<?php

namespace Documents;

/** @Document */
class Feature
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /**
     * @ReferenceOne(targetDocument="Product", inversedBy="features", cascade={"all"})
     */
    public $product;

    public function __construct($name)
    {
        $this->name = $name;
    }
}