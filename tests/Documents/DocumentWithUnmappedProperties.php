<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class DocumentWithUnmappedProperties
{
    #[ODM\Id]
    public string $id;

    public string $foo = 'bar';

    // We need at least one mapped field to avoid the native lazy object to be
    // switched to "initialized" state immediately after setting all its properties.
    #[ODM\Field]
    public string $mappedField;
}
