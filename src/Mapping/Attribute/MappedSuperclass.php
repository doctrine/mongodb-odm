<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass extends AbstractDocument
{
    public function __construct(
        public readonly ?string $repositoryClass = null,
        public readonly ?string $collection = null,
    ) {
    }
}
