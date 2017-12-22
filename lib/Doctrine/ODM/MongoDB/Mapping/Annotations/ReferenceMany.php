<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * Specifies a one-to-many relationship to a different document
 *
 * @Annotation
 */
final class ReferenceMany extends AbstractField
{
    public $type = 'many';
    public $reference = true;
    public $storeAs = ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF;
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
    public $strategy = CollectionHelper::DEFAULT_STRATEGY;
    public $collectionClass;
    public $prime = [];
}
