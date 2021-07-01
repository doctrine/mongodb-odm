<?php

declare(strict_types=1);

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
    /** @ODM\Id */
    public $id;
    /** @ODM\Field(type="string") */
    public $topic;
    /** @ODM\Field(type="string") */
    public $title;
    /** @ODM\Field(type="string") */
    public $text;
    /** @ODM\ReferenceOne(targetDocument=CmsUser::class) */
    public $user;
    /** @ODM\ReferenceMany(targetDocument=CmsComment::class) */
    public $comments;

    public function setAuthor(CmsUser $author): void
    {
        $this->user = $author;
    }

    public function addComment(CmsComment $comment): void
    {
        $this->comments[] = $comment;
        $comment->setArticle($this);
    }
}
