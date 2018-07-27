<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class ForumUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\ReferenceOne(targetDocument=ForumAvatar::class, cascade={"persist"}) */
    public $avatar;

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function setAvatar(ForumAvatar $avatar)
    {
        $this->avatar = $avatar;
    }
}
