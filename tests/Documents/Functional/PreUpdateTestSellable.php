<?php

namespace Documents\Functional;

/**
 * @EmbeddedDocument
 */
class PreUpdateTestSellable
{
    /** @ReferenceOne(targetDocument="PreUpdateTestProduct") */
    public $product;

    /** @ReferenceOne(targetDocument="PreUpdateTestSeller") */
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