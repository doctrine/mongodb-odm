<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Product
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
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceMany(targetDocument=Feature::class, mappedBy="product", cascade={"all"})
     *
     * @var Collection<int, Feature>
     */
    public $features;

    public function __construct(string $name)
    {
        $this->name     = $name;
        $this->features = new ArrayCollection();
    }

    public function addFeature(Feature $feature): void
    {
        $feature->product = $this;
        $this->features[] = $feature;
    }
}
