<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Specifies the change tracking policy for a document
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ChangeTrackingPolicy implements Annotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

// @phpstan-ignore class.notFound
class_alias(ChangeTrackingPolicy::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\ChangeTrackingPolicy::class);
