<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[Document]
class DocumentWithUnmappedProperties
{
    #[Id]
    public string $id;

    public string $foo = 'bar';
}
