<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Marks a method as a preLoad lifecycle callback
 *
 * @final
 */
#[Attribute(Attribute::TARGET_METHOD)]
class PreLoad implements MappingAttribute
{
}
