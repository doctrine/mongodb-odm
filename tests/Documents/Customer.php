<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Customer
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(name="cart", type="string") */
    public $cartTest;

    /**
     * @ODM\ReferenceOne(targetDocument="Cart", mappedBy="customer")
     */
    public $cart;
}
