<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Specifies a one-to-one relationship to a different document
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceOne extends AbstractField implements MappingAttribute
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
        public readonly string $storeAs = ClassMetadata::REFERENCE_STORE_AS_DB_REF,
        public readonly ?string $targetDocument = null,
        public readonly ?string $discriminatorField = null,
        public readonly ?array $discriminatorMap = null,
        public readonly ?string $defaultDiscriminatorValue = null,
        public readonly array|string|null $cascade = null,
        public readonly ?bool $orphanRemoval = null,
        public readonly ?string $inversedBy = null,
        public readonly ?string $mappedBy = null,
        public readonly ?string $repositoryMethod = null,
        public readonly array $sort = [],
        public readonly array $criteria = [],
        public readonly ?int $limit = null,
        public readonly ?int $skip = null,
    ) {
        parent::__construct($name, ClassMetadata::ONE, $nullable, $options, $strategy, $notSaved);
    }
}
