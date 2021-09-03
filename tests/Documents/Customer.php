<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Customer
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(name="cartTest", type="string")
     *
     * @var string|null
     */
    public $cartTest;

    /**
     * @ODM\ReferenceOne(targetDocument=Cart::class, mappedBy="customer")
     *
     * @var Cart|null
     */
    public $cart;
}
