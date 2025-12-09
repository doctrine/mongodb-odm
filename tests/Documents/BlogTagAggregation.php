<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\QueryResultDocument]
class BlogTagAggregation
{
    /** @var Tag|null */
    #[ODM\ReferenceOne(targetDocument: Tag::class, name: '_id')]
    public $tag;

    /** @var int|null */
    #[ODM\Field(type: 'int')]
    public $numPosts;
}
