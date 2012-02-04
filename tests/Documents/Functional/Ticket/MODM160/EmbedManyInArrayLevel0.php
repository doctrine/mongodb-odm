<?php

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

/** @ODM\Document(collection="embedded_test") */
class EmbedManyInArrayLevel0
{
    /** @ODM\Id */
    public $id;
    /** @ODM\String */
    public $name;
    /** @ODM\EmbedMany(targetDocument="EmbedManyInArrayLevel1") */
    public $level1 = array();
}