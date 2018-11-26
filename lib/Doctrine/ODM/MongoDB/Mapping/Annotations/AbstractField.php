<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;
use function sprintf;

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

    /**
     * Gets deprecation message. The method *WILL* be removed in 2.0.
     *
     * @internal
     */
    public function getDeprecationMessage() : string
    {
        return sprintf('%s will be removed in ODM 2.0. Use `@ODM\Field(type="%s")` instead.', static::class, $this->type);
    }

    /**
     * Gets whether the annotation is deprecated. The method *WILL* be removed in 2.0.
     *
     * @internal
     */
    public function isDeprecated() : bool
    {
        return false;
    }
}
