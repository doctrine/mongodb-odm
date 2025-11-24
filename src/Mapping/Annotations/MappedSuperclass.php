<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass extends AbstractDocument
{
    /** @var string|null */
    public $repositoryClass;

    public function __construct(?string $repositoryClass = null)
    {
        $this->repositoryClass = $repositoryClass;
    }
}
