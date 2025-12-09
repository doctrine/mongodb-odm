<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies a unique index on a field
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class UniqueIndex extends AbstractIndex implements MappingAttribute
{
    public function __construct(
        array $keys = [],
        ?string $name = null,
        ?bool $background = null,
        ?int $expireAfterSeconds = null,
        $order = null,
        bool $sparse = false,
        array $options = [],
        array $partialFilterExpression = [],
    ) {
        parent::__construct(
            $keys,
            $name,
            $background,
            $expireAfterSeconds,
            $order,
            true,
            $sparse,
            $options,
            $partialFilterExpression,
        );
    }
}
