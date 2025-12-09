<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class EmbedOneLevel1
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
    /** @var MODM160Level2|null */
    #[ODM\EmbedOne(targetDocument: MODM160Level2::class)]
    public $level2;
}
