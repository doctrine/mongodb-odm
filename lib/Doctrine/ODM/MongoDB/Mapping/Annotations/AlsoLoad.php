<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Loads data from a different field if the original field is not set
 *
 * @Annotation
 */
final class AlsoLoad extends Annotation
{
    /** @var string */
    public $name;
}
