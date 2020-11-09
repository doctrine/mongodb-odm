<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Loads data from a different field if the original field is not set
 *
 * @Annotation
 */
final class AlsoLoad implements NamedArgumentConstructorAnnotation
{
    /** @var string|string[] */
    public $value;

    /** @var string|null */
    public $name;

    public function __construct($value, ?string $name = null)
    {
        $this->value = $value;
        $this->name = $name;
    }
}
