<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\UnitOfWork;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * The parent association of an embedded document: the mapping it was found
 * through, the (possibly null) object that owns it, and the property path
 * from that parent.
 *
 * @internal
 *
 * @phpstan-import-type AssociationFieldMapping from ClassMetadata
 */
final class ParentAssociation
{
    /** @phpstan-param AssociationFieldMapping $mapping */
    public function __construct(
        public readonly array $mapping,
        public readonly ?object $parent,
        public readonly string $propertyPath,
    ) {
    }
}
