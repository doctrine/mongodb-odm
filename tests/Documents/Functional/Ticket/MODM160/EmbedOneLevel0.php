<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'embedded_test')]
class EmbedOneLevel0
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
    /** @var EmbedOneLevel1|null */
    #[ODM\EmbedOne(targetDocument: EmbedOneLevel1::class)]
    public $level1;
}
