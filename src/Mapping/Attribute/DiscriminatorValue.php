<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Use the specified discriminator for this class
 *
 * @Annotation
 * @NamedArgumentConstructor
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

// @phpstan-ignore class.notFound
class_alias(DiscriminatorValue::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorValue::class);
