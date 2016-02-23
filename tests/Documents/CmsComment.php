<?php

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
     */
    public $id;
    /**
     * @ODM\Field
     */
    public $topic;
    /**
     * @ODM\Field
     */
    public $text;
    /**
     * @ODM\ReferenceOne(targetDocument="CmsArticle")
     */
    public $article;

    /** @ODM\Field(name="ip", type="string") */
    public $authorIp;

    public function setArticle(CmsArticle $article) {
        $this->article = $article;
    }

    public function __toString() {
        return __CLASS__."[id=".$this->id."]";
    }
}
