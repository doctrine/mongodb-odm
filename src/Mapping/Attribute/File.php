<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Identifies a class as a GridFS file that can be stored in the database
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class File extends AbstractDocument
{
    /** @param Index[] $indexes */
    public function __construct(
        public readonly ?string $db = null,
        public readonly ?string $bucketName = null,
        public readonly ?string $repositoryClass = null,
        public readonly array $indexes = [],
        public readonly bool $readOnly = false,
        public readonly ?string $shardKey = null,
        public readonly string|int|null $writeConcern = null,
        public readonly ?int $chunkSizeBytes = null,
    ) {
    }
}
