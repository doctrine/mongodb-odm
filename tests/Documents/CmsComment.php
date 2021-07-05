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
class CmsComment
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Field */
    public $topic;
    /** @ODM\Field */
    public $text;
    /** @ODM\ReferenceOne(targetDocument=CmsArticle::class) */
    public $article;

    /** @ODM\Field(name="ip", type="string") */
    public $authorIp;

    /** @ODM\Field(type="string", nullable=true) */
    public $nullableField;

    public function setArticle(CmsArticle $article): void
    {
        $this->article = $article;
    }

    public function __toString()
    {
        return self::class . '[id=' . $this->id . ']';
    }
}
