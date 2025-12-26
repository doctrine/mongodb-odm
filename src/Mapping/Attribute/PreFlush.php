<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Marks a method as a preFlush lifecycle callback
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class PreFlush implements MappingAttribute
{
}
