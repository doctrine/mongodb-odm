<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class PreUpdateTestSellable
{
    /**
     * @ODM\ReferenceOne(targetDocument=PreUpdateTestProduct::class)
     *
     * @var PreUpdateTestProduct|null
     */
    public $product;

    /**
     * @ODM\ReferenceOne(targetDocument=PreUpdateTestSeller::class)
     *
     * @var PreUpdateTestSeller|null
     */
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
