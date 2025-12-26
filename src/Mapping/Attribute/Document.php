<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Identifies a class as a document that can be stored in the database
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Document extends AbstractDocument
{
    /**
     * @param string|array{name: string, capped?: bool, size?: int, max?: int}|null $collection
     * @param Index[]                                                               $indexes
     */
    public function __construct(
        public readonly ?string $db = null,
        public readonly string|array|null $collection = null,
        public readonly ?string $repositoryClass = null,
        public readonly array $indexes = [],
        public readonly bool $readOnly = false,
        public readonly ?string $shardKey = null,
        public readonly int|string|null $writeConcern = null,
    ) {
    }
}
