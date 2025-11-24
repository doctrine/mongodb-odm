<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Length extends AbstractField
{
    public function __construct()
    {
        parent::__construct('length', 'int', false, [], null, true);
    }
}
