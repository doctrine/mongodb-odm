<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

abstract class AbstractField extends Annotation
{
    /** @var string */
    public $name;

    /** @var string */
    public $type = 'string';

    /** @var bool */
    public $nullable = false;

    /** @var mixed[] */
    public $options = [];

    /** @var string|null */
    public $strategy;

    /** @var bool */
    public $notSaved = false;
}
