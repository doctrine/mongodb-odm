<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Defines a search index on a class.
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class SearchIndex implements Annotation
{
    /**
     * @param array<string, array>|null $fields
     * @param list<array>|null          $analyzers
     * @param bool|array|null           $storedSource
     * @param list<array>|null          $synonyms
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
