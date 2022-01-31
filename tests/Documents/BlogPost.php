<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class BlogPost
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
     * @var string|null
     */
    public $name;

    /**
     * @ODM\ReferenceMany(targetDocument=Tag::class, inversedBy="blogPosts", cascade={"all"})
     *
     * @var Collection<int, Tag>|array<Tag>
     */
    public $tags = [];

    /**
     * @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", cascade={"all"}, prime={"author"})
     *
     * @var Collection<int, Comment>|array<Comment>
     */
    public $comments = [];

    /**
     * @ODM\ReferenceOne(targetDocument=Comment::class, mappedBy="parent", sort={"date"="asc"})
     *
     * @var Comment|null
     */
    public $firstComment;

    /**
     * @ODM\ReferenceOne(targetDocument=Comment::class, mappedBy="parent", sort={"date"="desc"})
     *
     * @var Comment|null
     */
    public $latestComment;

    /**
     * @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", sort={"date"="desc"}, limit=5)
     *
     * @var Collection<int, Comment>|array<Comment>
     */
    public $last5Comments = [];

    /**
     * @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", criteria={"isByAdmin"=true}, sort={"date"="desc"})
     *
     * @var Collection<int, Comment>|array<Comment>
     */
    public $adminComments = [];

    /**
     * @ODM\ReferenceOne(targetDocument=Comment::class, mappedBy="parent", repositoryMethod="findOneComment")
     *
     * @var Comment|null
     */
    public $repoComment;

    /**
     * @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", repositoryMethod="findManyComments")
     *
     * @var Collection<int, Comment>
     */
    public $repoComments;

    /**
     * @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", repositoryMethod="findManyComments", prime={"author"})
     *
     * @var Collection<int, Comment>
     */
    public $repoCommentsWithPrimer;

    /**
     * @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", strategy="set", repositoryMethod="findManyComments")
     *
     * @var Collection<int, Comment>
     */
    public $repoCommentsSet;

    /**
     * @ODM\ReferenceMany(targetDocument=Comment::class, repositoryMethod="findManyComments")
     *
     * @var Collection<int, Comment>
     */
    public $repoCommentsWithoutMappedBy;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, inversedBy="posts", nullable=true)
     *
     * @var User|null
     */
    public $user;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addTag(Tag $tag): void
    {
        $tag->addBlogPost($this); // synchronously updating inverse side
        $this->tags[] = $tag;
    }

    public function addComment(Comment $comment): void
    {
        $comment->parent  = $this;
        $this->comments[] = $comment;
    }

    public function setUser(?User $user = null): void
    {
        $this->user = $user;
    }
}
