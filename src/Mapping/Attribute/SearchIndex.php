<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Defines a search index on a class.
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/index-definitions/
 *
 * @phpstan-import-type SearchIndexStoredSource from ClassMetadata
 * @phpstan-import-type SearchIndexSynonym from ClassMetadata
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class SearchIndex implements MappingAttribute
{
    /**
     * @param array<string, array>|null     $fields
     * @param list<array>|null              $analyzers
     * @param SearchIndexStoredSource|null  $storedSource
     * @param list<SearchIndexSynonym>|null $synonyms
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?bool $dynamic = null,
        public readonly ?array $fields = null,
        public readonly ?string $analyzer = null,
        public readonly ?string $searchAnalyzer = null,
        public readonly ?array $analyzers = null,
        public readonly bool|array|null $storedSource = null,
        public readonly ?array $synonyms = null,
    ) {
    }
}
