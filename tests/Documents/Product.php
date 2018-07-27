<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Product
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceMany(targetDocument=Feature::class, mappedBy="product", cascade={"all"}) */
    public $features;

    public function __construct($name)
    {
        $this->name = $name;
        $this->features = new ArrayCollection();
    }

    public function addFeature(Feature $feature)
    {
        $feature->product = $this;
        $this->features[] = $feature;
    }
}
