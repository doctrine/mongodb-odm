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
class Filename extends AbstractField
{
    public function __construct()
    {
        parent::__construct('filename', 'string', false, [], null, true);
    }
}

// @phpstan-ignore class.notFound
class_alias(Filename::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\File\Filename::class);
