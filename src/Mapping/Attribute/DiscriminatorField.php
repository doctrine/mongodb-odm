<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Specify a field name to store a discriminator value
 *
 * @Annotation
 * @NamedArgumentConstructor
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

// @phpstan-ignore class.notFound
class_alias(DiscriminatorField::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorField::class);
