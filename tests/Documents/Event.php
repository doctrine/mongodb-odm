<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(db="doctrine_odm_tests", collection="events") */
class Event
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceOne(targetDocument="Documents\User") */
    private $user;

    /** @ODM\String */
    private $title;

    /** @ODM\String */
    private $type;

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