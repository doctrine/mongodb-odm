<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Identifies a class as a document that can be stored in the database
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Document extends AbstractDocument
{
    /**
     * @param string|array{name: string, capped?: bool, size?: int, max?: int}|null $collection
     * @param Index[]                                                               $indexes
     */
    public function __construct(
        public ?string $db = null,
        public string|array|null $collection = null,
        public ?string $repositoryClass = null,
        public array $indexes = [],
        public bool $readOnly = false,
        public ?string $shardKey = null,
        public int|string|null $writeConcern = null,
    ) {
    }
}
