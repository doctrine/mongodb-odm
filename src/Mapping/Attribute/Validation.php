<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
final class Validation implements MappingAttribute
{
    /**
     * @phpstan-param ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR|ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN|null $action
     * @phpstan-param ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF|ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT|ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE|null $level
     */
    public function __construct(
        public readonly ?string $validator = null,
        public readonly ?string $action = null,
        public readonly ?string $level = null,
    ) {
    }
}
