<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Event
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceOne(targetDocument="Documents\User") */
    private $user;

    /** @ODM\Field(type="string") */
    private $title;

    /** @ODM\Field(type="string") */
    private $type;

    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }
}
