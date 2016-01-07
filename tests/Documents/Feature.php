<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Feature
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument="Product", inversedBy="features", cascade={"all"})
     */
    public $product;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
