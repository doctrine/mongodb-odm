<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class CmsGroup
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\ReferenceMany(targetDocument=CmsUser::class) */
    public $users;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addUser(CmsUser $user)
    {
        $this->users[] = $user;
    }

    public function getUsers()
    {
        return $this->users;
    }
}
