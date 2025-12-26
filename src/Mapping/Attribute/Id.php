<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Special field mapping to map document identifiers
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id extends AbstractField implements MappingAttribute
{
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
