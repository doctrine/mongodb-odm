<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\GH683;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'gh683_test')]
class ParentDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var AbstractEmbedded|null */
    #[ODM\EmbedOne(targetDocument: AbstractEmbedded::class)]
    public $embedOne;

    /** @var Collection<int, AbstractEmbedded> */
    #[ODM\EmbedMany(targetDocument: AbstractEmbedded::class)]
    public $embedMany;
}
