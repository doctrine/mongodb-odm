<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Embeds a single document
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EmbedOne extends AbstractField implements MappingAttribute
{
    /** @param array<string, class-string>|null $discriminatorMap */
    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        public readonly ?string $targetDocument = null,
        public readonly ?string $discriminatorField = null,
        public readonly ?array $discriminatorMap = null,
        public readonly ?string $defaultDiscriminatorValue = null,
    ) {
        parent::__construct($name, ClassMetadata::ONE, $nullable, $options, $strategy, $notSaved);
    }
}
