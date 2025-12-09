<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Specifies the change tracking policy for a document
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ChangeTrackingPolicy implements MappingAttribute
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
