<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Tag
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, BlogPost> */
    #[ODM\ReferenceMany(targetDocument: BlogPost::class, mappedBy: 'tags')]
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
