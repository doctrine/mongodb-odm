<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Tag
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceMany(targetDocument=BlogPost::class, mappedBy="tags")
     *
     * @var Collection<int, BlogPost>
     */
    public $blogPosts;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addBlogPost(BlogPost $blogPost): void
    {
        $this->blogPosts[] = $blogPost;
    }
}
