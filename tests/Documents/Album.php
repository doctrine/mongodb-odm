<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="albums") */
class Album
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * @ODM\EmbedMany(targetDocument=Song::class)
     *
     * @var Collection<int, Song>
     */
    private $songs;

    public function __construct(string $name)
    {
        $this->name  = $name;
        $this->songs = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addSong(Song $song): void
    {
        $this->songs[] = $song;
    }

    /** @return Collection<int, Song> */
    public function getSongs(): Collection
    {
        return $this->songs;
    }
}
