<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies a parent class that other documents may extend to inherit mapping
 * information
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class MappedSuperclass extends AbstractDocument
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
