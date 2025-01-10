<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Specify a map of discriminator values and classes
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class DiscriminatorMap implements Annotation
{
    /** @var array<class-string> */
    public $value;

    /** @param array<class-string> $value */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
