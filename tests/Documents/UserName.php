<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\View(
 *     rootClass=CmsUser::class,
 *     repositoryClass=UserNameRepository::class,
 *     view="user-name"
 * )
 */
class UserName
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $username;

    public function getId() : ?string
    {
        return $this->id;
    }

    public function getUsername() : ?string
    {
        return $this->username;
    }
}
