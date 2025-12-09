<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use function class_exists;

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractIndex::class);

// @phpstan-ignore-next-line if.alwaysFalse
if (false) {
    /** @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractIndex instead */
    abstract class AbstractIndex extends \Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractIndex
    {
    }
}
