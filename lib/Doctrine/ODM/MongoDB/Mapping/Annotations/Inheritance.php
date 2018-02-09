<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Specifies inheritance mapping for a document
 *
 * @Annotation
 */
final class Inheritance extends Annotation
{
    public $type = 'NONE';
    public $discriminatorMap = [];
    public $discriminatorField;
    public $defaultDiscriminatorValue;
}
