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
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field
     *
     * @var string|null
     */
    public $topic;

    /**
     * @ODM\Field
     *
     * @var string|null
     */
    public $text;

    /**
     * @ODM\ReferenceOne(targetDocument=CmsArticle::class)
     *
     * @var CmsArticle|null
     */
    public $article;

    /**
     * @ODM\Field(name="ip", type="string")
     *
     * @var string|null
     */
    public $authorIp;

    /**
     * @ODM\Field(type="string", nullable=true)
     *
     * @var string|null
     */
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
