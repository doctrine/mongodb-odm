<?php

namespace Documents\Functional;

/**
 * @Document(collection="pre_update_test_product")
 */
class PreUpdateTestProduct
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @EmbedOne(targetDocument="PreUpdateTestSellable") */
    public $sellable;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}