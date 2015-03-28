<?php

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class EmbedManyInArrayCollectionLevel1
{
    /** @ODM\String */
    public $name;
    /** @ODM\EmbedMany(targetDocument="MODM160Level2") */
    public $level2;

    public function __construct()
    {
        $this->level2 = new ArrayCollection();
    }
}
