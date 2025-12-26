<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Marks a method as a preUpdate lifecycle callback
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PreUpdate implements MappingAttribute
{
}
