<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\QueryResultDocument */
class BlogTagAggregation
{
    /**
     * @ODM\ReferenceOne(targetDocument=Tag::class, name="_id")
     *
     * @var Tag|null
     */
    public $tag;

    /**
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    public $numPosts;
}
