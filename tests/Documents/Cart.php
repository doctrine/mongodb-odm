<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Cart
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="int")
     *
     * @var int
     */
    public $numItems = 0;

    /**
     * @ODM\ReferenceOne(targetDocument=Customer::class, inversedBy="cart")
     *
     * @var Customer|null
     */
    public $customer;
}
