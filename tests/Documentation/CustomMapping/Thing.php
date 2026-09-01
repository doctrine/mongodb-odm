<?php

declare(strict_types=1);

namespace Documentation\CustomMapping;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;

#[Document]
class Thing
{
    #[Id]
    public string $id;

    #[Field(type: 'date_with_timezone')]
    public ?DateTimeImmutable $date = null;
}
