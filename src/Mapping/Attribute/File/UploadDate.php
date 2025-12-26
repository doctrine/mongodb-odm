<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute\File;

use Attribute;
use Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractField;
use Doctrine\ODM\MongoDB\Mapping\Attribute\MappingAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class UploadDate extends AbstractField implements MappingAttribute
{
    public function __construct()
    {
        parent::__construct('uploadDate', 'date', false, [], null, true);
    }
}
