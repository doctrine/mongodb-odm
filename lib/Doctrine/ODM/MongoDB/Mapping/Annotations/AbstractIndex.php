<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

abstract class AbstractIndex extends Annotation
{
    /** @var string[] */
    public $keys = [];

    /** @var string */
    public $name;

    /** @var bool|null */
    public $background;

    /** @var int|null */
    public $expireAfterSeconds;

    /** @var string|int|null */
    public $order;

    /** @var bool */
    public $unique = false;

    /** @var bool */
    public $sparse = false;

    /** @var mixed[] */
    public $options = [];

    /** @var array */
    public $partialFilterExpression = [];
}
