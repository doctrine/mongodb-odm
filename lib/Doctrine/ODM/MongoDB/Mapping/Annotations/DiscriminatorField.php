<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Specify a field name to store a discriminator value
 *
 * @Annotation
 */
final class DiscriminatorField implements NamedArgumentConstructorAnnotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
