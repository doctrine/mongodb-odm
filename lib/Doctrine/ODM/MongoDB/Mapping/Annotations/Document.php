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
    public $db;
    public $collection;
    public $repositoryClass;
    public $indexes = [];
    public $readOnly = false;
    public $shardKey;
    public $writeConcern;
}
