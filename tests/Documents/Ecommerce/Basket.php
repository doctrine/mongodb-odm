<?php

declare(strict_types=1);

namespace Documents\Ecommerce;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Basket
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\ReferenceMany(targetDocument=Documents\Ecommerce\ConfigurableProduct::class)
     *
     * @var Collection<int, ConfigurableProduct>|array<ConfigurableProduct>
     */
    protected $products = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    /** @return Collection<int, ConfigurableProduct>|array<ConfigurableProduct> */
    public function getProducts()
    {
        return $this->products;
    }
}
