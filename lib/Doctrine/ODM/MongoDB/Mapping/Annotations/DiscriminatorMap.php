<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Specify a map of discriminator values and classes
 *
 * @Annotation
 */
final class DiscriminatorMap implements NamedArgumentConstructorAnnotation
{
    /** @var string[] */
    public $value;

    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
