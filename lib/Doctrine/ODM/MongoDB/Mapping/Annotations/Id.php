<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Special field mapping to map document identifiers
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id extends AbstractField
{
    /** @var bool */
    public $id = true;

    /** @var string|null */
    public $type;

    /** @var string */
    public $strategy = 'auto';

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = 'auto',
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
