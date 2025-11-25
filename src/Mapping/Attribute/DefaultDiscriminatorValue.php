<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Specifies a default discriminator value to be used when the discriminator
 * field is not set in a document
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class DefaultDiscriminatorValue implements Annotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

// @phpstan-ignore class.notFound
class_alias(DefaultDiscriminatorValue::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\DefaultDiscriminatorValue::class);
