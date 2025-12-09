<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use function class_exists;

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractDocument::class);

// @phpstan-ignore-next-line if.alwaysFalse
if (false) {
    /** @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractDocument instead */
    abstract class AbstractDocument extends \Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractDocument
    {
    }
}
