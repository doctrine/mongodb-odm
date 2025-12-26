<?php

declare(strict_types=1);

namespace Documentation\Validation;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;

#[Document]
class Customer
{
    #[Id]
    public string $id;

    public function __construct(
        #[Field]
        public float $orderLimit,
    ) {
    }
}
