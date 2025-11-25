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
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Length extends AbstractField
{
    public function __construct()
    {
        parent::__construct('length', 'int', false, [], null, true);
    }
}

// @phpstan-ignore class.notFound
class_alias(Length::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\File\Length::class);
