<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\Indexes({
 *   @ODM\Index(keys={"topic"="asc"})
 * })
 */
class CmsArticle
{
    /**
     * @ODM\Id
     */
    public $id;
    /**
     * @ODM\String
     */
    public $topic;
    /**
     * @ODM\String
     */
    public $text;
    /**
     * @ODM\ReferenceOne(targetDocument="CmsUser")
     */
    public $user;
    /**
     * @ODM\ReferenceMany(targetDocument="CmsComment")
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
