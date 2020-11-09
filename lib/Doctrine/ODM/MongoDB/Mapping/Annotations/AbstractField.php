<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;
use function sprintf;

abstract class AbstractField implements NamedArgumentConstructorAnnotation
{
    /** @var string */
    public $name;

    /** @var string|null */
    public $type;

    /** @var bool */
    public $nullable;

    /** @var mixed[] */
    public $options;

    /** @var string|null */
    public $strategy;

    /** @var bool */
    public $notSaved;

    public function __construct(
        ?string $name = null,
        ?string $type = 'string',
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->options = $options;
        $this->strategy = $strategy;
        $this->notSaved = $notSaved;
    }

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
