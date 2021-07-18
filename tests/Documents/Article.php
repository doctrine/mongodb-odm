<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use function array_search;
use function in_array;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $title;

    /** @ODM\Field(type="string") */
    private $body;

    /** @ODM\Field(type="date") */
    private $createdAt;

    /** @ODM\Field(type="collection") */
    private $tags = [];

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body): void
    {
        $this->body = $body;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function addTag($tag): void
    {
        $this->tags[] = $tag;
    }

    public function removeTag($tag): void
    {
        if (! in_array($tag, $this->tags)) {
            return;
        }

        unset($this->tags[array_search($tag, $this->tags)]);
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
