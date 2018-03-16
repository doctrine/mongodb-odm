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
    /** @var string */
    public $type = 'NONE';

    /** @var string[] */
    public $discriminatorMap = [];

    /** @var string|null */
    public $discriminatorField;

    /** @var string|null */
    public $defaultDiscriminatorValue;
}
