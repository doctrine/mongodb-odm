<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute\File;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractField;
use Doctrine\ODM\MongoDB\Mapping\Attribute\MappingAttribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Metadata extends AbstractField implements MappingAttribute
{
    /** @param array<string, class-string>|null $discriminatorMap */
    public function __construct(
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        public readonly ?string $targetDocument = null,
        public readonly ?string $discriminatorField = null,
        public readonly ?array $discriminatorMap = null,
        public readonly ?string $defaultDiscriminatorValue = null,
    ) {
        parent::__construct('metadata', ClassMetadata::ONE, $nullable, $options, $strategy, $notSaved);
    }
}
