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
    /** @var string */
    public $type = 'one';

    /** @var bool */
    public $reference = true;

    /** @var string */
    public $storeAs = ClassMetadata::REFERENCE_STORE_AS_DB_REF;

    /** @var string|null */
    public $targetDocument;

    /** @var string|null */
    public $discriminatorField;

    /** @var string[]|null */
    public $discriminatorMap;

    /** @var string|null */
    public $defaultDiscriminatorValue;

    /** @var string[]|null */
    public $cascade;

    /** @var bool|null */
    public $orphanRemoval;

    /** @var string|null */
    public $inversedBy;

    /** @var string|null */
    public $mappedBy;

    /** @var string|null */
    public $repositoryMethod;

    /** @var array */
    public $sort = [];

    /** @var array */
    public $criteria = [];

    /** @var int|null */
    public $limit;

    /** @var int|null */
    public $skip;
}
