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
    /** @var bool */
    public $reference = true;

    /** @var string */
    public $storeAs;

    /** @var string|null */
    public $targetDocument;

    /** @var string|null */
    public $discriminatorField;

    /** @var array<string, class-string>|null */
    public $discriminatorMap;

    /** @var string|null */
    public $defaultDiscriminatorValue;

    /** @var bool|null */
    public $orphanRemoval;

    /** @var string|null */
    public $inversedBy;

    /** @var string|null */
    public $mappedBy;

    /** @var string|null */
    public $repositoryMethod;

    /** @var array<string, string|int> */
    public $sort;

    /** @var array<string, mixed> */
    public $criteria;

    /** @var int|null */
    public $limit;

    /** @var int|null */
    public $skip;

    /** @var string|null */
    public $collectionClass;

    /** @var string[] */
    public $prime;

    /** @var bool */
    public $storeEmptyArray;

    /**
     * @param array<string, class-string>|null $discriminatorMap
     * @param string[]|string|null             $cascade
     * @param array<string, string|int>        $sort
     * @param array<string, mixed>             $criteria
     * @param string[]                         $prime
     */
    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        string $strategy = CollectionHelper::DEFAULT_STRATEGY,
        bool $notSaved = false,
        string $storeAs = ClassMetadata::REFERENCE_STORE_AS_DB_REF,
        ?string $targetDocument = null,
        ?string $discriminatorField = null,
        ?array $discriminatorMap = null,
        ?string $defaultDiscriminatorValue = null,
        public $cascade = null,
        ?bool $orphanRemoval = null,
        ?string $inversedBy = null,
        ?string $mappedBy = null,
        ?string $repositoryMethod = null,
        array $sort = [],
        array $criteria = [],
        ?int $limit = null,
        ?int $skip = null,
        ?string $collectionClass = null,
        array $prime = [],
        bool $storeEmptyArray = false,
    ) {
        parent::__construct($name, ClassMetadata::MANY, $nullable, $options, $strategy, $notSaved);

        $this->storeAs                   = $storeAs;
        $this->targetDocument            = $targetDocument;
        $this->discriminatorField        = $discriminatorField;
        $this->discriminatorMap          = $discriminatorMap;
        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
        $this->orphanRemoval             = $orphanRemoval;
        $this->inversedBy                = $inversedBy;
        $this->mappedBy                  = $mappedBy;
        $this->repositoryMethod          = $repositoryMethod;
        $this->sort                      = $sort;
        $this->criteria                  = $criteria;
        $this->limit                     = $limit;
        $this->skip                      = $skip;
        $this->collectionClass           = $collectionClass;
        $this->prime                     = $prime;
        $this->storeEmptyArray           = $storeEmptyArray;
    }
}
