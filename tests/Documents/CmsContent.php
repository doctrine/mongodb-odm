<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\MappedSuperclass
 */
abstract class CmsContent extends CmsPage
{
    /**
     * @ODM\Field(type="string")
     */
    public $title;
}
