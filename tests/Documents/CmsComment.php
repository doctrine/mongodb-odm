<?php

namespace Documents;

/**
 * @Document
 */
class CmsComment
{
    /**
     * @Id
     */
    public $id;
    /**
     * @Field
     */
    public $topic;
    /**
     * @Field
     */
    public $text;
    /**
     * @ReferenceOne(targetDocument="CmsArticle")
     */
    public $article;

    public function setArticle(CmsArticle $article) {
        $this->article = $article;
    }

    public function __toString() {
        return __CLASS__."[id=".$this->id."]";
    }
}
