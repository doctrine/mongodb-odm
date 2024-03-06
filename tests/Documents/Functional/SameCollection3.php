<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Sample document without discriminator field to test defaultDiscriminatorValue
 */
#[ODM\Document(collection: 'same_collection')]
class SameCollection3
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $test;
}
