<?php

namespace Documents;

/** @Document */
class Product
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /**
     * @ReferenceMany(targetDocument="Feature", mappedBy="product", cascade={"all"})
     */
    public $features;

    public function __construct($name)
    {
        $this->name = $name;
        $this->features = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addFeature(Feature $feature)
    {
        $feature->product = $this;
        $this->features[] = $feature;
    }
}