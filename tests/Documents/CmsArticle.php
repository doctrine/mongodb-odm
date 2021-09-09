<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
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
     *
     * @var string|null
     */
    public $id;
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $topic;
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $title;
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $text;
    /**
     * @ODM\ReferenceOne(targetDocument=CmsUser::class)
     *
     * @var CmsUser|null
     */
    public $user;
    /**
     * @ODM\ReferenceMany(targetDocument=CmsComment::class)
     *
     * @var Collection<int, CmsComment>
     */
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
