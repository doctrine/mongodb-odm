<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class CmsProduct extends CmsContent
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}
