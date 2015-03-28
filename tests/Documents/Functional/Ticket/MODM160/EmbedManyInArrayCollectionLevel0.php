<?php

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="embedded_test") */
class EmbedManyInArrayCollectionLevel0
{
    /** @ODM\Id */
    public $id;
    /** @ODM\String */
    public $name;
    /** @ODM\EmbedMany(targetDocument="EmbedManyInArrayCollectionLevel1") */
    public $level1;

    public function __construct()
    {
        $this->level1 = new ArrayCollection();
    }
}
