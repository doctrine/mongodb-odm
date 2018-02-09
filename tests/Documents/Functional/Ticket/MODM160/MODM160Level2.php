<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class MODM160Level2
{
    /** @ODM\Field(type="string") */
    public $name;
}
