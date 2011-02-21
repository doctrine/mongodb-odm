<?php

namespace Documents;

/** @Document */
class Customer
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @String(name="cart") */
    public $cartTest;

    /**
     * @ReferenceOne(targetDocument="Cart", mappedBy="customer")
     */
    public $cart;
}