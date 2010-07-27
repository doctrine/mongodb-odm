<?php

namespace Documents\Functional;

/**
 * @Document(collection="same_collection")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"test1"="Documents\Functional\SameCollection1", "test2"="Documents\Functional\SameCollection2"})
 */
class SameCollection1
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @String */
    public $test;
}