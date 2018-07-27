<?php

declare(strict_types=1);

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

    /** @ODM\ReferenceOne(targetDocument=Cart::class, mappedBy="customer") */
    public $cart;
}
