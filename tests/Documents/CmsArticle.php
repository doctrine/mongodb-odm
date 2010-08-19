<?php

namespace Documents;

/**
 * @Document
 * @Indexes({
 *   @Index(keys={"topic"="asc"})
 * })
 */
class CmsArticle
{
    /**
     * @Id
     */
    public $id;
    /**
     * @String
     */
    public $topic;
    /**
     * @String
     */
    public $text;
    /**
     * @ReferenceOne(targetDocument="CmsUser")
     */
    public $user;
    /**
     * @ReferenceMany(targetDocument="CmsComment")
     */
    public $comments;

    public function setAuthor(CmsUser $author) {
        $this->user = $author;
    }

    public function addComment(CmsComment $comment) {
        $this->comments[] = $comment;
        $comment->setArticle($this);
    }
}
