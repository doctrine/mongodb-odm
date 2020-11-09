<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Identifies a class as a document that can be stored in the database
 *
 * @Annotation
 */
final class Document extends AbstractDocument implements NamedArgumentConstructorAnnotation
{
    /** @var string|null */
    public $db;

    /** @var array|string|null */
    public $collection;

    /** @var string|null */
    public $repositoryClass;

    /** @var Index[] */
    public $indexes = [];

    /** @var bool */
    public $readOnly = false;

    /** @var string|null */
    public $shardKey;

    /** @var string|int|null */
    public $writeConcern;

    public function __construct(
        ?string $db = null,
        $collection = null,
        ?string $repositoryClass = null,
        array $indexes = [],
        bool $readOnly = false,
        ?string $shardKey = null,
        $writeConcern = null
    ) {
        $this->db = $db;
        $this->collection = $collection;
        $this->repositoryClass = $repositoryClass;
        $this->indexes = $indexes;
        $this->readOnly = $readOnly;
        $this->shardKey = $shardKey;
        $this->writeConcern = $writeConcern;
    }
}
