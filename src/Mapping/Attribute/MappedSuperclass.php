<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class MappedSuperclass extends AbstractDocument
{
    public function __construct(public ?string $repositoryClass = null, public ?string $collection = null)
    {
    }
}
