<?php

namespace Documents;

/** @Document */
class Profile
{
    /** @Id */
    private $id;

    /** @Field */
    private $name;

    /** @ReferenceOne(targetDocument="Documents\Image") */
    private $image;

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
}