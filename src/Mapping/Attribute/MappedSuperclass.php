<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass extends AbstractDocument
{
    /** @var string|null */
    public $repositoryClass;

    /** @var string|null */
    public $collection;

    public function __construct(?string $repositoryClass = null, ?string $collection = null)
    {
        $this->repositoryClass = $repositoryClass;
        $this->collection      = $collection;
    }
}

// @phpstan-ignore class.notFound
class_alias(MappedSuperclass::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\MappedSuperclass::class);
