<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="embedded_test") */
class EmbeddedTestLevel0
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedMany(targetDocument="EmbeddedTestLevel1") */
    public $level1 = array();
}
