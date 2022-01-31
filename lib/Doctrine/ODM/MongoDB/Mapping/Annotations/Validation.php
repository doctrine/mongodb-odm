<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Validation implements Annotation
{
    /** @var string|null */
    public $validator;

    /**
     * @var string|null
     * @Enum({
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR,
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
     *     })
     */
    public $action;

    /**
     * @var string|null
     * @Enum({
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF,
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT,
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
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
