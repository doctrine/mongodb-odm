<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Specifies a unique index on a field
 *
 * @Annotation
 */
final class UniqueIndex extends AbstractIndex
{
    public function __construct(
        array $keys = [],
        ?string $name = null,
        ?bool $background = null,
        ?int $expireAfterSeconds = null,
        $order = null,
        bool $sparse = false,
        array $options = [],
        array $partialFilterExpression = []
    ) {
        parent::__construct($keys, $name, $background, $expireAfterSeconds, $order, true, $sparse, $options, $partialFilterExpression);
    }
}
