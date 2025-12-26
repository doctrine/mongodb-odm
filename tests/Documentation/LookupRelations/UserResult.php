<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;

#[Document]
class UserResult
{
    #[Id]
    public string $id;

    #[Field(type: 'string')]
    public string $name;

    /** @var Collection<int, UserOrderResult> */
    #[EmbedMany(targetDocument: UserOrderResult::class)]
    public Collection $orders;
}
