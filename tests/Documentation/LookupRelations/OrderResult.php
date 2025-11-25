<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedOne;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;
use Doctrine\ODM\MongoDB\Mapping\Attribute\QueryResultDocument;

#[QueryResultDocument]
class OrderResult
{
    #[Id]
    public string $id;

    #[Field(type: 'date_immutable')]
    public DateTimeImmutable $date;

    /** @var Collection<int, Item> */
    #[EmbedMany(targetDocument: Item::class)]
    public Collection $items;

    #[EmbedOne(targetDocument: User::class)]
    public User $user;
}
