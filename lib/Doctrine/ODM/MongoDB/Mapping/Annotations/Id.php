<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Special field mapping to map document identifiers
 *
 * @Annotation
 */
final class Id extends AbstractField
{
    public $id = true;
    public $type;
    public $strategy = 'auto';
}
