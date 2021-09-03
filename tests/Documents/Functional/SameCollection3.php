<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Sample document without discriminator field to test defaultDiscriminatorValue
 *
 * @ODM\Document(collection="same_collection")
 */
class SameCollection3
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $test;
}
