<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 *
 * @Annotation
 */
final class MappedSuperclass extends AbstractDocument
{
    /** @var string */
    public $repositoryClass;
}
