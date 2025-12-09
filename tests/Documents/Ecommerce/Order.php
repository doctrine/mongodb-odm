<?php

declare(strict_types=1);

namespace Documents\Ecommerce;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Order
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var Collection<int, ConfigurableProduct>|array<ConfigurableProduct> */
    #[ODM\ReferenceMany(targetDocument: ConfigurableProduct::class, strategy: 'addToSet', storeEmptyArray: true)]
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
