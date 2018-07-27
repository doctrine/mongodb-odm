<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Cart
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="int") */
    public $numItems = 0;

    /** @ODM\ReferenceOne(targetDocument=Customer::class, inversedBy="cart") */
    public $customer;
}
