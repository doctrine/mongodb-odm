<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class ForumUser
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|int|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\ReferenceOne(targetDocument=ForumAvatar::class, cascade={"persist"})
     *
     * @var ForumAvatar|null
     */
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

    public function setAvatar(ForumAvatar $avatar): void
    {
        $this->avatar = $avatar;
    }
}
