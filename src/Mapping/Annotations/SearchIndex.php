<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\SearchIndex as SearchIndexAttribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Defines a search index on a class.
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\SearchIndex instead
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/index-definitions/
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @phpstan-import-type SearchIndexStoredSource from ClassMetadata
 * @phpstan-import-type SearchIndexSynonym from ClassMetadata
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class SearchIndex extends SearchIndexAttribute implements Annotation
{
}
