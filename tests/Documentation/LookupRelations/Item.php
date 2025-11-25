<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;

#[Document]
class Item
{
    #[Id]
    public ?string $id = null;

    public function __construct(
        #[Field(type: 'string')]
        public ?string $name = null,
    ) {
    }
}
