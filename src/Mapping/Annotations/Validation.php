<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
class Validation implements Annotation
{
    /** @var string|null */
    public $validator;

    /** @var ClassMetadata::SCHEMA_VALIDATION_ACTION_ERROR|ClassMetadata::SCHEMA_VALIDATION_ACTION_WARN|null */
    public $action;

    /** @var ClassMetadata::SCHEMA_VALIDATION_LEVEL_OFF|ClassMetadata::SCHEMA_VALIDATION_LEVEL_STRICT|ClassMetadata::SCHEMA_VALIDATION_LEVEL_MODERATE|null */
    public $level;

    public function __construct(?string $validator = null, ?string $action = null, ?string $level = null)
    {
        $this->validator = $validator;
        $this->action    = $action;
        $this->level     = $level;
    }
}
