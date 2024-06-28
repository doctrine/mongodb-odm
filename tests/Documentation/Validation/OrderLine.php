<?php

declare(strict_types=1);

namespace Documentation\Validation;

use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[EmbeddedDocument]
class OrderLine
{
    #[Id]
    public string $id;

    public function __construct(
        #[Field(type: 'float')]
        public float $amount,
    ) {
    }
}
