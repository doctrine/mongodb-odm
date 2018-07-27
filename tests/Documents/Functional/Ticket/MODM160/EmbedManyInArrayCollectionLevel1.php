<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class EmbedManyInArrayCollectionLevel1
{
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedMany(targetDocument=MODM160Level2::class) */
    public $level2;

    public function __construct()
    {
        $this->level2 = new ArrayCollection();
    }
}
