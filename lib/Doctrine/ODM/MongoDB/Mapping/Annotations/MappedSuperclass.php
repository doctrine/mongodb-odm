<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 *
 * @Annotation
 */
final class MappedSuperclass extends AbstractDocument implements NamedArgumentConstructorAnnotation
{
    /** @var string */
    public $repositoryClass;

    public function __construct(?string $repositoryClass)
    {
        $this->repositoryClass = $repositoryClass;
    }
}
