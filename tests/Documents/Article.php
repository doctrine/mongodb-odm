<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @Document(collection="articles")
 */
class Article
{
    /** @Id */
    private $id;

    /** @String */
    private $title;

    /** @String */
    private $body;

    /** @Date */
    private $createdAt;

    /** @Field(type="collection", strategy="set") */
    private $tags = array();

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    public function removeTag($tag)
    {
        if ( ! in_array($tag, $this->tags))
        {
            return;
        }
        unset($this->tags[array_search($tag, $this->tags)]);
    }

    public function getTags()
    {
        return $this->tags;
    }
}