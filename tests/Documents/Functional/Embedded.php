<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Embedded
{
    /** @ODM\Field(type="string") */
    public $name;
}
