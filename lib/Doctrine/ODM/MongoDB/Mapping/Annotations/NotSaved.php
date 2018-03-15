<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Specifies that a field will not be written to the database
 *
 * @Annotation
 */
final class NotSaved extends AbstractField
{
    /** @var bool */
    public $notSaved = true;
}
