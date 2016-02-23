<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\MappedSuperclass
 * @ODM\Indexes({
 *   @ODM\Index(keys={"slug"="asc"}, options={"unique"="true"})
 * })
 */
abstract class CmsPage
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     */
    public $slug;
}
