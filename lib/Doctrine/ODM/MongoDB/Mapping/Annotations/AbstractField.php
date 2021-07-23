<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

abstract class AbstractField implements Annotation
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

    public function __construct(
        ?string $name = null,
        string $type = 'string',
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
