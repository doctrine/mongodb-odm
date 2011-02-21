<?php

namespace Documents;

/** @Document */
class Tag
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @ReferenceMany(targetDocument="BlogPost", mappedBy="tags") */
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