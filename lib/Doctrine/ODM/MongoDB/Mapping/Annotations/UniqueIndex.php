<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Specifies a unique index on a field
 *
 * @Annotation
 */
final class UniqueIndex extends AbstractIndex
{
    public $unique = true;
}
