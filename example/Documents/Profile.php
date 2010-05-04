<?php

namespace Documents;

/** @Document */
class Profile
{
    /** @Id */
    private $id;

    /** @Field */
    private $name;

    /** @ReferenceOne(targetDocument="Documents\Image", cascade={"all"}) */
    private $image;

    /** @ReferenceMany(targetDocument="Documents\Song", cascade={"all"}) */
    protected $songs;

    public function __construct()
    {
        $this->songs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
      return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage(Image $image)
    {
        $this->image = $image;
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