<?php

declare(strict_types=1);

namespace Documentation\BlendingOrm;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'orders')]
class Order
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[Column(type: 'string')]
    private string $productId;

    private Product $product;

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProduct(Product $product): void
    {
        $this->productId = $product->id;
        $this->product   = $product;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }
}
