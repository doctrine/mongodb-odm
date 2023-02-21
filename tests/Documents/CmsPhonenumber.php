<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class CmsPhonenumber
{
    /**
     * @ODM\Id(strategy="NONE", type="custom_id")
     *
     * @var int|string|null
     */
    public $phonenumber;

    /**
     * @ODM\ReferenceOne(targetDocument=CmsUser::class, cascade={"merge"})
     *
     * @var CmsUser|null
     */
    public $user;

    public function setUser(CmsUser $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?CmsUser
    {
        return $this->user;
    }
}
