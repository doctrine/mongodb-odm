<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class BlogPost
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceMany(targetDocument=Tag::class, inversedBy="blogPosts", cascade={"all"}) */
    public $tags = [];

    /** @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", cascade={"all"}, prime={"author"}) */
    public $comments = [];

    /** @ODM\ReferenceOne(targetDocument=Comment::class, mappedBy="parent", sort={"date"="asc"}) */
    public $firstComment;

    /** @ODM\ReferenceOne(targetDocument=Comment::class, mappedBy="parent", sort={"date"="desc"}) */
    public $latestComment;

    /** @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", sort={"date"="desc"}, limit=5) */
    public $last5Comments = [];

    /** @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", criteria={"isByAdmin"=true}, sort={"date"="desc"}) */
    public $adminComments = [];

    /** @ODM\ReferenceOne(targetDocument=Comment::class, mappedBy="parent", repositoryMethod="findOneComment") */
    public $repoComment;

    /** @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", repositoryMethod="findManyComments") */
    public $repoComments;

    /** @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", repositoryMethod="findManyComments", prime={"author"}) */
    public $repoCommentsWithPrimer;

    /** @ODM\ReferenceMany(targetDocument=Comment::class, mappedBy="parent", strategy="set", repositoryMethod="findManyComments") */
    public $repoCommentsSet;

    /** @ODM\ReferenceMany(targetDocument=Comment::class, repositoryMethod="findManyComments") */
    public $repoCommentsWithoutMappedBy;

    /** @ODM\ReferenceOne(targetDocument=User::class, inversedBy="posts", nullable=true) */
    public $user;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function getName()
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
