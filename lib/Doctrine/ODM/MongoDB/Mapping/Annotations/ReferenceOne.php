<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Specifies a one-to-one relationship to a different document
 *
 * @Annotation
 */
final class ReferenceOne extends AbstractField
{
    public $type = 'one';
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
    public $sort = array();
    public $criteria = array();
    public $limit;
    public $skip;
}
