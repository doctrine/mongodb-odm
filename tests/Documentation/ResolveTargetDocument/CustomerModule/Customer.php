<?php

declare(strict_types=1);

namespace Documentation\ResolveTargetDocument\CustomerModule;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[Document]
abstract class Customer
{
    #[Id]
    public string $id;

    #[Field]
    public string $name;
}
