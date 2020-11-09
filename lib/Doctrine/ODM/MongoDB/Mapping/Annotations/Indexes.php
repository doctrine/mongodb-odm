<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;
use function is_array;

/**
 * Specifies a list of indexes for a document
 *
 * @deprecated class was deprecated in doctrine/mongodb-odm 2.2 and will be removed in 3.0. Specify all Index and UniqueIndex annotations on a class level.
 *
 * @Annotation
 */
final class Indexes implements NamedArgumentConstructorAnnotation
{
    /** @var AbstractIndex[] */
    public $value;

    public function __construct($value)
    {
        $this->value = is_array($value) ? $value : [$value];
    }
}
