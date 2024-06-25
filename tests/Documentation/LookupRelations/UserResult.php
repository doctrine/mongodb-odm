<?php

declare(strict_types=1);

namespace Documentation\LookupRelations;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[Document]
class UserResult
{
    #[Id]
    public string $id;

    #[Field(type: 'string')]
    public string $name;

    /** @var Collection<UserOrderResult> */
    #[EmbedMany(targetDocument: UserOrderResult::class)]
    public Collection $orders;
}
