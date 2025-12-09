<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Specify a map of discriminator values and classes
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class DiscriminatorMap implements Annotation
{
    /** @var array<class-string> */
    public $value;

    /** @param array<class-string> $value */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}

// @phpstan-ignore class.notFound
class_alias(DiscriminatorMap::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorMap::class);
