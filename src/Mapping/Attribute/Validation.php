<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @Target({"CLASS"})
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Validation implements MappingAttribute
{
    /** @var string|null */
    public $validator;

    /**
     * @var string|null
     * @Enum({
     *     ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR,
     *     ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
     *     })
     */
    public $action;

    /**
     * @var string|null
     * @Enum({
     *     ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF,
     *     ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT,
     *     ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
     *     })
     */
    public $level;

    public function __construct(?string $validator = null, ?string $action = null, ?string $level = null)
    {
        $this->validator = $validator;
        $this->action    = $action;
        $this->level     = $level;
    }
}
