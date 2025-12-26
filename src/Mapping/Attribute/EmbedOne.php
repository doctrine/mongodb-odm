<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Embeds a single document
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class EmbedOne extends AbstractField implements MappingAttribute
{
    /** @param array<string, class-string>|null $discriminatorMap */
    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        public ?string $targetDocument = null,
        public ?string $discriminatorField = null,
        public ?array $discriminatorMap = null,
        public ?string $defaultDiscriminatorValue = null,
    ) {
        parent::__construct($name, ClassMetadata::ONE, $nullable, $options, $strategy, $notSaved);
    }
}
