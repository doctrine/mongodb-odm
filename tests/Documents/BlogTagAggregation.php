<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\QueryResultDocument
 */
class BlogTagAggregation
{
    /** @ODM\ReferenceOne(targetDocument="Tag", name="_id") */
    public $tag;

    /** @ODM\Field(type="int") */
    public $numPosts;
}
