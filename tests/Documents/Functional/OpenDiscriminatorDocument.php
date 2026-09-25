<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Declares a discriminator field without a discriminator map, allowing any
 * subclass to be persisted; its discriminator value falls back to its own
 * class name. Used to exercise that fallback in PersistenceBuilder.
 */
#[ODM\Document(collection: 'open_discriminator')]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField('type')]
class OpenDiscriminatorDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}
