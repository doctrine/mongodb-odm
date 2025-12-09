<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class PreUpdateTestSellable
{
    /** @var PreUpdateTestProduct|null */
    #[ODM\ReferenceOne(targetDocument: PreUpdateTestProduct::class)]
    public $product;

    /** @var PreUpdateTestSeller|null */
    #[ODM\ReferenceOne(targetDocument: PreUpdateTestSeller::class)]
    public $seller;

    public function getProduct(): ?PreUpdateTestProduct
    {
        return $this->product;
    }

    public function getSeller(): ?PreUpdateTestSeller
    {
        return $this->seller;
    }
}
