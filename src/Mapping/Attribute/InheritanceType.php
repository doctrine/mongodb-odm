<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies which inheritance type to use for a document
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class InheritanceType implements MappingAttribute
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
