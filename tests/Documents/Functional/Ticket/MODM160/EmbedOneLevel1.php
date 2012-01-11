<?php

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

/** @ODM\EmbeddedDocument */
class EmbedOneLevel1
{
    /** @ODM\String */
    public $name;
    /** @ODM\EmbedOne(targetDocument="MODM160Level2") */
    public $level2;
}