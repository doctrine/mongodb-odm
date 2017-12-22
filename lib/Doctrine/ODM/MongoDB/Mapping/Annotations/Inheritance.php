<?php

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
    public $discriminatorMap = array();
    public $discriminatorField;
    public $defaultDiscriminatorValue;
}
