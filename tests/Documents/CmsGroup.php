<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class CmsGroup
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\ReferenceMany(targetDocument=CmsUser::class)
     *
     * @var Collection<int, CmsUser>
     */
    public $users;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addUser(CmsUser $user): void
    {
        $this->users[] = $user;
    }

    /** @return Collection<int, CmsUser> */
    public function getUsers(): Collection
    {
        return $this->users;
    }
}
