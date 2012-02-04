<?php

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

/** @ODM\Document(collection="embedded_test") */
class EmbedOneLevel0
{
    /** @ODM\Id */
    public $id;
    /** @ODM\String */
    public $name;
    /** @ODM\EmbedOne(targetDocument="EmbedOneLevel1") */
    public $level1;
}