<?php

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

/** @ODM\EmbeddedDocument */
class EmbedManyInArrayCollectionLevel1
{
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedMany(targetDocument="MODM160Level2") */
    public $level2;

    public function __construct()
    {
        $this->level2 = new ArrayCollection();
    }
}
