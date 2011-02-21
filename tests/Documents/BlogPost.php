<?php

namespace Documents;

/** @Document */
class BlogPost
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @ReferenceMany(targetDocument="Tag", inversedBy="blogPosts", cascade={"all"}) */
    public $tags = array();

    /** @ReferenceMany(targetDocument="Comment", mappedBy="parent", cascade={"all"}) */
    public $comments = array();

    /** @ReferenceOne(targetDocument="Comment", mappedBy="parent", sort={"date"="asc"}) */
    public $firstComment;

    /** @ReferenceOne(targetDocument="Comment", mappedBy="parent", sort={"date"="desc"}) */
    public $latestComment;

    /** @ReferenceMany(targetDocument="Comment", mappedBy="parent", sort={"date"="desc"}, limit=5) */
    public $last5Comments = array();

    /** @ReferenceMany(targetDocument="Comment", mappedBy="parent", criteria={"isByAdmin"=true}, sort={"date"="desc"}) */
    public $adminComments = array();

    /** @ReferenceOne(targetDocument="Comment", mappedBy="parent", repositoryMethod="findOneComment") */    
    public $repoComment;

    /** @ReferenceMany(targetDocument="Comment", mappedBy="parent", repositoryMethod="findManyComments") */    
    public $repoComments;

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
}