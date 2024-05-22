<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Index(keys: ['topic' => 'asc'])]
#[ODM\SearchIndex(dynamic: true)]
#[ODM\Document]
class CmsArticle
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $topic;
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $title;
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $text;
    /** @var CmsUser|null */
    #[ODM\ReferenceOne(targetDocument: CmsUser::class)]
    public $user;
    /** @var Collection<int, CmsComment> */
    #[ODM\ReferenceMany(targetDocument: CmsComment::class)]
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
