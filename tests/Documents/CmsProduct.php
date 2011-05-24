<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class CmsProduct extends CmsContent
{
    /**
     * @ODM\String
     */
    public $name;
}
