<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Product
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, Feature> */
    #[ODM\ReferenceMany(targetDocument: Feature::class, mappedBy: 'product', cascade: ['all'])]
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
