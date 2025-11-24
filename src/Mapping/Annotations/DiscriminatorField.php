<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

/**
 * Specify a field name to store a discriminator value
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorField implements Annotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
