<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\ReferenceOne;

#[Document]
class Order
{
    #[Id]
    public string $id;

    #[Field(type: 'date_immutable')]
    public DateTimeImmutable $date;

    /** @var Collection<Item> */
    #[ReferenceMany(
        targetDocument: Item::class,
        cascade: 'all',
        storeAs: 'id',
    )]
    public Collection $items;

    #[ReferenceOne(
        targetDocument: User::class,
        cascade: 'all',
        storeAs: 'id',
    )]
    public User $user;

    public function __construct()
    {
        $this->date  = new DateTimeImmutable();
        $this->items = new ArrayCollection();
    }
}
