<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class PreUpdateTestSellable
{
    /** @ODM\ReferenceOne(targetDocument="PreUpdateTestProduct") */
    public $product;

    /** @ODM\ReferenceOne(targetDocument="PreUpdateTestSeller") */
    public $seller;

    public function getProduct()
    {
        return $this->product;
    }

    public function getSeller()
    {
        return $this->seller;
    }
}