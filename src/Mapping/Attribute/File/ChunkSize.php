<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute\File;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractField;

use function class_alias;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ChunkSize extends AbstractField
{
    public function __construct()
    {
        parent::__construct('chunkSize', 'int', false, [], null, true);
    }
}

// @phpstan-ignore class.notFound
class_alias(ChunkSize::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\File\ChunkSize::class);
