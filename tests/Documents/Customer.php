<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Customer
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\String(name="cart") */
    public $cartTest;

    /**
     * @ODM\ReferenceOne(targetDocument="Cart", mappedBy="customer")
     */
    public $cart;
}