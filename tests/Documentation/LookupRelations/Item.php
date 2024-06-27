<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

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
