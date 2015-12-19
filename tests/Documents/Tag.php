<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Tag
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceMany(targetDocument="BlogPost", mappedBy="tags") */
    public $blogPosts;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function addBlogPost(BlogPost $blogPost)
    {
        $this->blogPosts[] = $blogPost;
    }
}
