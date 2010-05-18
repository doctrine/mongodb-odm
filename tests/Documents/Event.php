<?php

namespace Documents;

/** @Document(db="doctrine_odm_tests", collection="events") */
class Event
{
    /** @Id */
    private $id;

    /** @ReferenceOne(targetDocument="Documents\User") */
    private $user;

    /** @String */
    private $title;

    /** @String */
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