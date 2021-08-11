<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

abstract class AbstractField implements Annotation
{
    /** @var string|null */
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
        $this->name     = $name;
        $this->type     = $type;
        $this->nullable = $nullable;
        $this->options  = $options;
        $this->strategy = $strategy;
        $this->notSaved = $notSaved;
    }
}
