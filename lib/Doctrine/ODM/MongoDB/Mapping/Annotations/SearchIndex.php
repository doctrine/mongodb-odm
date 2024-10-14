<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Defines a search index on a class.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @phpstan-import-type SearchIndexStoredSource from ClassMetadata
 * @phpstan-import-type SearchIndexSynonym from ClassMetadata
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class SearchIndex implements Annotation
{
    /**
     * @param array<string, array>|null     $fields
     * @param list<array>|null              $analyzers
     * @param SearchIndexStoredSource|null  $storedSource
     * @param list<SearchIndexSynonym>|null $synonyms
     */
    public function __construct(
        public ?string $name = null,
        public ?bool $dynamic = null,
        public ?array $fields = null,
        public ?string $analyzer = null,
        public ?string $searchAnalyzer = null,
        public ?array $analyzers = null,
        public $storedSource = null,
        public ?array $synonyms = null,
    ) {
    }
}
