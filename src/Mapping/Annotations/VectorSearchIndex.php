<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Defines a vector search index on a class.
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\VectorSearchIndex instead
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-vector-search/vector-search-type/
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @phpstan-import-type VectorSearchIndexField from ClassMetadata
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class VectorSearchIndex extends \Doctrine\ODM\MongoDB\Mapping\Attribute\VectorSearchIndex implements Annotation
{
}
