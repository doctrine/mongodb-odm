<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Specifies a unique index on a field
 *
 * @Annotation
 */
final class UniqueIndex extends AbstractIndex
{
    /** @var bool */
    public $unique = true;
}
