<?php

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

/** @ODM\EmbeddedDocument */
class EmbedManyInArrayLevel1
{
    /** @ODM\String */
    public $name;
    /** @ODM\EmbedMany(targetDocument="MODM160Level2") */
    public $level2 = array();
}