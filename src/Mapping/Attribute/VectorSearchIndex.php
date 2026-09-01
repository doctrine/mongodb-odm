<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Defines a vector search index on a class.
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-vector-search/vector-search-type/
 *
 * @phpstan-import-type VectorSearchIndexField from ClassMetadata
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class VectorSearchIndex implements MappingAttribute
{
    /** @param list<VectorSearchIndexField> $fields */
    public function __construct(
        public readonly array $fields,
        public readonly ?string $name = null,
    ) {
    }
}
