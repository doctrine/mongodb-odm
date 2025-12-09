<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Marks a method as a postPersist lifecycle callback
 *
 * @final
 */
#[Attribute(Attribute::TARGET_METHOD)]
class PostPersist implements MappingAttribute
{
}
