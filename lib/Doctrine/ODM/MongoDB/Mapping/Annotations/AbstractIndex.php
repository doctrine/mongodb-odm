<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

abstract class AbstractIndex implements NamedArgumentConstructorAnnotation
{
    /** @var string[] */
    public $keys;

    /** @var string */
    public $name;

    /** @var bool|null */
    public $background;

    /** @var int|null */
    public $expireAfterSeconds;

    /** @var string|int|null */
    public $order;

    /** @var bool */
    public $unique;

    /** @var bool */
    public $sparse;

    /** @var mixed[] */
    public $options;

    /** @var array */
    public $partialFilterExpression;

    public function __construct(
        array $keys = [],
        ?string $name = null,
        ?bool $background = null,
        ?int $expireAfterSeconds = null,
        $order = null,
        bool $unique = false,
        bool $sparse = false,
        array $options = [],
        array $partialFilterExpression = []
    ) {
        $this->keys = $keys;
        $this->name = $name;
        $this->background = $background;
        $this->expireAfterSeconds = $expireAfterSeconds;
        $this->order = $order;
        $this->unique = $unique;
        $this->sparse = $sparse;
        $this->options = $options;
        $this->partialFilterExpression = $partialFilterExpression;
    }
}
