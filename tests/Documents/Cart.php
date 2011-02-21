<?php

namespace Documents;

/** @Document */
class Cart
{
    /** @Id */
    public $id;

    /** @Int */
    public $numItems = 0;

    /**
     * @ReferenceOne(targetDocument="Customer", inversedBy="cart")
     */
    public $customer;
}