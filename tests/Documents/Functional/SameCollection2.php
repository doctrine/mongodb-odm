<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="same_collection")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"test1"="Documents\Functional\SameCollection1", "test2"="Documents\Functional\SameCollection2"})
 * @ODM\DefaultDiscriminatorValue("test1")
 */
class SameCollection2
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\String */
    public $ok;

    /** @ODM\String */
    public $w00t;
}
