<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class CmsProduct extends CmsContent
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}
