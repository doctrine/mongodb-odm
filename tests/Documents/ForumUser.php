<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

#[ODM\Document]
class ForumUser
{
    /** @var ObjectId|int|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var ForumAvatar|null */
    #[ODM\ReferenceOne(targetDocument: ForumAvatar::class, cascade: ['persist'])]
    public $avatar;

    /** @return int|ObjectId|null */
    public function getId()
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getAvatar(): ?ForumAvatar
    {
        return $this->avatar;
    }

    public function setAvatar(ForumAvatar $avatar): void
    {
        $this->avatar = $avatar;
    }
}
