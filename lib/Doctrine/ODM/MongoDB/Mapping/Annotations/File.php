<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Identifies a class as a GridFS file that can be stored in the database
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class File extends AbstractDocument
{
    /** @var string|null */
    public $db;

    /** @var string|null */
    public $bucketName;

    /** @var string|null */
    public $repositoryClass;

    /** @var Index[] */
    public $indexes = [];

    /** @var bool bool */
    public $readOnly = false;

    /** @var string|null */
    public $shardKey;

    /** @var string|int|null */
    public $writeConcern;

    /** @var int|null */
    public $chunkSizeBytes;

    public function __construct(
        ?string $db = null,
        ?string $bucketName = null,
        ?string $repositoryClass = null,
        array $indexes = [],
        bool $readOnly = false,
        ?string $shardKey = null,
        $writeConcern = null,
        ?int $chunkSizeBytes = null
    ) {
        $this->db              = $db;
        $this->bucketName      = $bucketName;
        $this->repositoryClass = $repositoryClass;
        $this->indexes         = $indexes;
        $this->readOnly        = $readOnly;
        $this->shardKey        = $shardKey;
        $this->writeConcern    = $writeConcern;
        $this->chunkSizeBytes  = $chunkSizeBytes;
    }
}
