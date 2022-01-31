<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Validation
{
    /** @var string */
    public $validator;

    /**
     * @var string
     * @Enum({
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR,
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN,
     *     })
     */
    public $action;

    /**
     * @var string
     * @Enum({
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF,
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT,
     *     \Doctrine\ODM\MongoDB\Mapping\ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE,
     *     })
     */
    public $level;
}
