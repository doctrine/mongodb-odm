<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Specifies a one-to-one relationship to a different document
 *
 * @Annotation
 */
final class ReferenceOne extends AbstractField
{
    public $type = 'one';
    public $reference = true;
    public $storeAs = ClassMetadata::REFERENCE_STORE_AS_DB_REF;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
    public $defaultDiscriminatorValue;
    public $cascade;
    public $orphanRemoval;
    public $inversedBy;
    public $mappedBy;
    public $repositoryMethod;
    public $sort = [];
    public $criteria = [];
    public $limit;
    public $skip;
}
