<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Use the specified discriminator for this class
 *
 * @Annotation
 */
final class DiscriminatorValue implements NamedArgumentConstructorAnnotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
