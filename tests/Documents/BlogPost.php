<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\User;

/** @ODM\Document */
class BlogPost
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceMany(targetDocument="Tag", inversedBy="blogPosts", cascade={"all"}) */
    public $tags = array();

    /** @ODM\ReferenceMany(targetDocument="Comment", mappedBy="parent", cascade={"all"}) */
    public $comments = array();

    /** @ODM\ReferenceOne(targetDocument="Comment", mappedBy="parent", sort={"date"="asc"}) */
    public $firstComment;

    /** @ODM\ReferenceOne(targetDocument="Comment", mappedBy="parent", sort={"date"="desc"}) */
    public $latestComment;

    /** @ODM\ReferenceMany(targetDocument="Comment", mappedBy="parent", sort={"date"="desc"}, limit=5) */
    public $last5Comments = array();

    /** @ODM\ReferenceMany(targetDocument="Comment", mappedBy="parent", criteria={"isByAdmin"=true}, sort={"date"="desc"}) */
    public $adminComments = array();

    /** @ODM\ReferenceOne(targetDocument="Comment", mappedBy="parent", repositoryMethod="findOneComment") */
    public $repoComment;

    /** @ODM\ReferenceMany(targetDocument="Comment", mappedBy="parent", repositoryMethod="findManyComments") */
    public $repoComments;

    /** @ODM\ReferenceMany(targetDocument="Comment", mappedBy="parent", strategy="set", repositoryMethod="findManyComments") */
    public $repoCommentsSet;

    /** @ODM\ReferenceMany(targetDocument="Comment", repositoryMethod="findManyComments") */
    public $repoCommentsWithoutMappedBy;

    /** @ODM\ReferenceMany(targetDocument="Comment", mappedBy="parent", repositoryMethod="findManyCommentsEager") */
    public $repoCommentsEager;

    /** @ODM\ReferenceOne(targetDocument="User", inversedBy="posts", nullable=true) */
    public $user;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addTag(Tag $tag)
    {
        $tag->addBlogPost($this); // synchronously updating inverse side
        $this->tags[] = $tag;
    }

    public function addComment(Comment $comment)
    {
        $comment->parent = $this;
        $this->comments[] = $comment;
    }

    public function setUser(User $user = null)
    {
        $this->user = $user;
    }
}
