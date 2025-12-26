<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * Embeds multiple documents
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class EmbedMany extends AbstractField implements MappingAttribute
{
    /** @param array<string, class-string>|null $discriminatorMap */
    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        string $strategy = CollectionHelper::DEFAULT_STRATEGY,
        bool $notSaved = false,
        public ?string $targetDocument = null,
        public ?string $discriminatorField = null,
        public ?array $discriminatorMap = null,
        public ?string $defaultDiscriminatorValue = null,
        public ?string $collectionClass = null,
        public bool $storeEmptyArray = false,
    ) {
        parent::__construct($name, ClassMetadata::MANY, $nullable, $options, $strategy, $notSaved);
    }
}
