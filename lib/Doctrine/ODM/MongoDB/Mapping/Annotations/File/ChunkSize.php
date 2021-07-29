<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ChunkSize extends AbstractField
{
    public function __construct(?string $name = 'chunkSize')
    {
        parent::__construct($name, 'int', false, [], null, true);
    }
}
