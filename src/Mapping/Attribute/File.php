<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Identifies a class as a GridFS file that can be stored in the database
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class File extends AbstractDocument
{
    /** @param Index[] $indexes */
    public function __construct(
        public ?string $db = null,
        public ?string $bucketName = null,
        public ?string $repositoryClass = null,
        public array $indexes = [],
        public bool $readOnly = false,
        public ?string $shardKey = null,
        public string|int|null $writeConcern = null,
        public ?int $chunkSizeBytes = null,
    ) {
    }
}
