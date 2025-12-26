<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Specifies a one-to-one relationship to a different document
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ReferenceOne extends AbstractField implements MappingAttribute
{
    /**
     * @param class-string|null                $targetDocument
     * @param array<string, class-string>|null $discriminatorMap
     * @param string[]|string|null             $cascade
     * @param array<string, string|int>        $sort
     * @param array<string, mixed>             $criteria
     */
    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        public string $storeAs = ClassMetadata::REFERENCE_STORE_AS_DB_REF,
        public ?string $targetDocument = null,
        public ?string $discriminatorField = null,
        public ?array $discriminatorMap = null,
        public ?string $defaultDiscriminatorValue = null,
        public array|string|null $cascade = null,
        public ?bool $orphanRemoval = null,
        public ?string $inversedBy = null,
        public ?string $mappedBy = null,
        public ?string $repositoryMethod = null,
        public array $sort = [],
        public array $criteria = [],
        public ?int $limit = null,
        public ?int $skip = null,
    ) {
        parent::__construct($name, ClassMetadata::ONE, $nullable, $options, $strategy, $notSaved);
    }
}
