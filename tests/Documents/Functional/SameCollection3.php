<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Sample document without discriminator field to test defaultDiscriminatorValue
 * @ODM\Document(collection="same_collection")
 */
class SameCollection3
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\String */
    public $test;
}
