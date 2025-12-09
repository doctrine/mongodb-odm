<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Specifies which inheritance type to use for a document
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class InheritanceType implements Annotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

// @phpstan-ignore class.notFound
class_alias(InheritanceType::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\InheritanceType::class);
