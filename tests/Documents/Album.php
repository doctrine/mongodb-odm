<?php

namespace Documents;

/** @Document(collection="albums") */
class Album
{
    /** @Id */
    private $id;

    /** @String */
    private $name;

    /** @EmbedMany(targetDocument="Song") */
    private $songs = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addSong(Song $song)
    {
        $this->songs[] = $song;
    }

    public function getSongs()
    {
        return $this->songs;
    }
}