<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * Specifies a one-to-many relationship to a different document
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceMany extends AbstractField
{
    /** @var string */
    public $type = ClassMetadata::MANY;

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

    /** @var string[]|string|null */
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

    /** @var string */
    public $strategy = CollectionHelper::DEFAULT_STRATEGY;

    /** @var string|null */
    public $collectionClass;

    /** @var string[] */
    public $prime = [];

    /**
     * @param array                $options
     * @param array|null           $discriminatorMap
     * @param string|string[]|null $cascade
     * @param array                $sort
     * @param array                $criteria
     * @param array                $prime
     */
    public function __construct(
        ?string $name = null,
        string $type = ClassMetadata::MANY,
        bool $nullable = false,
        array $options = [],
        bool $notSaved = false,
        bool $reference = true,
        string $storeAs = ClassMetadata::REFERENCE_STORE_AS_DB_REF,
        ?string $targetDocument = null,
        ?string $discriminatorField = null,
        ?array $discriminatorMap = null,
        ?string $defaultDiscriminatorValue = null,
        $cascade = null,
        ?bool $orphanRemoval = null,
        ?string $inversedBy = null,
        ?string $mappedBy = null,
        ?string $repositoryMethod = null,
        array $sort = [],
        array $criteria = [],
        ?int $limit = null,
        ?int $skip = null,
        string $strategy = CollectionHelper::DEFAULT_STRATEGY,
        ?string $collectionClass = null,
        array $prime = []
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);

        $this->type                      = $type;
        $this->reference                 = $reference;
        $this->storeAs                   = $storeAs;
        $this->targetDocument            = $targetDocument;
        $this->discriminatorField        = $discriminatorField;
        $this->discriminatorMap          = $discriminatorMap;
        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
        $this->cascade                   = $cascade;
        $this->orphanRemoval             = $orphanRemoval;
        $this->inversedBy                = $inversedBy;
        $this->mappedBy                  = $mappedBy;
        $this->repositoryMethod          = $repositoryMethod;
        $this->sort                      = $sort;
        $this->criteria                  = $criteria;
        $this->limit                     = $limit;
        $this->skip                      = $skip;
        $this->strategy                  = $strategy;
        $this->collectionClass           = $collectionClass;
        $this->prime                     = $prime;
    }
}
