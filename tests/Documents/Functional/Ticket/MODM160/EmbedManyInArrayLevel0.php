<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'embedded_test')]
class EmbedManyInArrayLevel0
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, EmbedManyInArrayLevel1>|array<EmbedManyInArrayLevel1> */
    #[ODM\EmbedMany(targetDocument: EmbedManyInArrayLevel1::class)]
    public $level1 = [];
}
