<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Event
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceOne(targetDocument=User::class) */
    private $user;

    /** @ODM\Field(type="string") */
    private $title;

    /** @ODM\Field(type="string") */
    private $type;

    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user): void
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

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }
}
