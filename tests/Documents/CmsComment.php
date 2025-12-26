<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Index;

#[Index(keys: ['topic' => 'asc'])]
#[ODM\Document]
class CmsComment
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field]
    public $topic;

    /** @var string|null */
    #[ODM\Field]
    public $text;

    /** @var CmsArticle|null */
    #[ODM\ReferenceOne(targetDocument: CmsArticle::class)]
    public $article;

    /** @var string|null */
    #[ODM\Field(name: 'ip', type: 'string')]
    public $authorIp;

    /** @var string|null */
    #[ODM\Field(type: 'string', nullable: true)]
    public $nullableField;

    public function setArticle(CmsArticle $article): void
    {
        $this->article = $article;
    }

    public function __toString(): string
    {
        return self::class . '[id=' . $this->id . ']';
    }
}
