<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

/**
 * Use the specified discriminator for this class
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorValue implements Annotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
