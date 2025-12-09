<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Cart
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var int */
    #[ODM\Field(type: 'int')]
    public $numItems = 0;

    /** @var Customer|null */
    #[ODM\ReferenceOne(targetDocument: Customer::class, inversedBy: 'cart')]
    public $customer;
}
