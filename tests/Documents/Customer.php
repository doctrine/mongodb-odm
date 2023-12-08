<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Customer
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var string|null */
    #[ODM\Field(name: 'cartTest', type: 'string')]
    public $cartTest;

    /** @var Cart|null */
    #[ODM\ReferenceOne(targetDocument: Cart::class, mappedBy: 'customer')]
    public $cart;
}
