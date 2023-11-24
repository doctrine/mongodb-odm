<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

abstract class AbstractIndex implements Annotation
{
    /** @var string[] */
    public $keys;

    /** @var string|null */
    public $name;

    /** @var bool|null */
    public $background;

    /** @var int|null */
    public $expireAfterSeconds;

    /** @var bool */
    public $unique;

    /** @var bool */
    public $sparse;

    /** @var mixed[] */
    public $options;

    /** @var array<string, mixed> */
    public $partialFilterExpression;

    /**
     * @param string[]             $keys
     * @param string|int|null      $order
     * @param mixed[]              $options
     * @param array<string, mixed> $partialFilterExpression
     */
    public function __construct(
        array $keys = [],
        ?string $name = null,
        ?bool $background = null,
        ?int $expireAfterSeconds = null,
        public $order = null,
        bool $unique = false,
        bool $sparse = false,
        array $options = [],
        array $partialFilterExpression = [],
    ) {
        $this->keys                    = $keys;
        $this->name                    = $name;
        $this->background              = $background;
        $this->expireAfterSeconds      = $expireAfterSeconds;
        $this->unique                  = $unique;
        $this->sparse                  = $sparse;
        $this->options                 = $options;
        $this->partialFilterExpression = $partialFilterExpression;
    }
}
