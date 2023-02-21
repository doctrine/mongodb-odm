<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass */
abstract class CmsContent extends CmsPage
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $title;
}
