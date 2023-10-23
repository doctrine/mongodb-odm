<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Identifies a class as a document that can be stored in the database
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Document extends AbstractDocument
{
    /** @var string|null */
    public $db;

    /** @var string|null */
    public $repositoryClass;

    /** @var Index[] */
    public $indexes;

    /** @var bool */
    public $readOnly;

    /** @var string|null */
    public $shardKey;

    /**
     * @param string|array{name: string, capped?: bool, size?: int, max?: int}|null $collection
     * @param Index[]                                                               $indexes
     * @param int|string|null                                                       $writeConcern
     */
    public function __construct(
        ?string $db = null,
        public $collection = null,
        ?string $repositoryClass = null,
        array $indexes = [],
        bool $readOnly = false,
        ?string $shardKey = null,
        public $writeConcern = null,
    ) {
        $this->db              = $db;
        $this->repositoryClass = $repositoryClass;
        $this->indexes         = $indexes;
        $this->readOnly        = $readOnly;
        $this->shardKey        = $shardKey;
    }
}
