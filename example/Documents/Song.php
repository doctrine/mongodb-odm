<?php

namespace Documents;

/** @Document */
class Song
{
    /** @Id */
    private $id;

    /** @Field */
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
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
}