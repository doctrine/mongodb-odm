<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @ODM\View(
 *     rootClass=CmsUser::class,
 *     repositoryClass=UserNameRepository::class,
 *     view="user-name"
 * )
 */
class UserName
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $username;

    /**
     * @ODM\ReferenceOne(targetDocument=ViewReference::class, name="_id", storeAs=ClassMetadata::REFERENCE_STORE_AS_ID, notSaved=true)
     *
     * @var ViewReference|null
     */
    private $viewReference;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getViewReference(): ?ViewReference
    {
        return $this->viewReference;
    }
}
