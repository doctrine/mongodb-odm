<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Must be set on a document class to instruct Doctrine to check for lifecycle
 * callback annotations on public methods.
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HasLifecycleCallbacks implements MappingAttribute
{
}
