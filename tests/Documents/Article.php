<?php

declare(strict_types=1);

namespace Documents;

use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\UTCDateTime;

use function array_search;
use function in_array;

#[ODM\Document(collection: 'articles')]
class Article
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $title;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $body;

    /** @var string|UTCDateTime|DateTimeInterface|null */
    #[ODM\Field(type: 'date')]
    private $createdAt;

    /** @var int[] */
    #[ODM\Field(type: 'collection')]
    private $tags = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /** @return DateTimeInterface|UTCDateTime|string|null */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /** @param DateTimeInterface|UTCDateTime|string|null $createdAt */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function addTag(int $tag): void
    {
        $this->tags[] = $tag;
    }

    public function removeTag(int $tag): void
    {
        if (! in_array($tag, $this->tags)) {
            return;
        }

        unset($this->tags[array_search($tag, $this->tags)]);
    }

    /** @return int[] */
    public function getTags(): array
    {
        return $this->tags;
    }
}
