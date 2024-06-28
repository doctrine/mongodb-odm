<?php

declare(strict_types=1);

namespace Documentation\Validation;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[Document]
class Customer
{
    #[Id]
    public string $id;

    public function __construct(
        #[Field(type: 'float')]
        public float $orderLimit,
    ) {
    }
}
