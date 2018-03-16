<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Identifies a class as a document that can be stored in the database
 *
 * @Annotation
 */
final class Document extends AbstractDocument
{
    /** @var string|null */
    public $db;

    /** @var string|null */
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
}
