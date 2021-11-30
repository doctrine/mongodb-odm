<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class EmbedOneLevel1
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
    /**
     * @ODM\EmbedOne(targetDocument=MODM160Level2::class)
     *
     * @var MODM160Level2|null
     */
    public $level2;
}
