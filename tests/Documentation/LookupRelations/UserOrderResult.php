<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\QueryResultDocument;

#[QueryResultDocument]
class UserOrderResult
{
    #[Id]
    public string $id;

    #[Field(type: 'date_immutable')]
    public DateTimeImmutable $date;

    /** @var Collection<Item> */
    #[EmbedMany(targetDocument: Item::class)]
    public Collection $items;
}
