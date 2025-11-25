<?php

declare(strict_types=1);

namespace Documentation\ResolveTargetDocument\CustomerModule;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;

#[Document]
abstract class Customer
{
    #[Id]
    public string $id;

    #[Field]
    public string $name;
}
