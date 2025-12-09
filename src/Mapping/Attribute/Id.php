<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Special field mapping to map document identifiers
 *
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Id extends AbstractField implements MappingAttribute
{
    /** @var bool */
    public $id = true;

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = 'auto',
        bool $notSaved = false,
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);
    }
}
