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
final class EmbedMany extends AbstractField implements MappingAttribute
{
    /** @param array<string, class-string>|null $discriminatorMap */
    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        string $strategy = CollectionHelper::DEFAULT_STRATEGY,
        bool $notSaved = false,
        public readonly ?string $targetDocument = null,
        public readonly ?string $discriminatorField = null,
        public readonly ?array $discriminatorMap = null,
        public readonly ?string $defaultDiscriminatorValue = null,
        public readonly ?string $collectionClass = null,
        public readonly bool $storeEmptyArray = false,
    ) {
        parent::__construct($name, ClassMetadata::MANY, $nullable, $options, $strategy, $notSaved);
    }
}
