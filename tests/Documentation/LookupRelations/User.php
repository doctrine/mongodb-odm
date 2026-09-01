<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;

#[Document]
class User
{
    #[Id]
    public string $id;

    public function __construct(
        #[Field(type: 'string')]
        public string $name,
    ) {
    }
}
