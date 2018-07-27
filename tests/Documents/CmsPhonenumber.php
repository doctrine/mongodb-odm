<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class CmsPhonenumber
{
    /** @ODM\Id(strategy="NONE", type="custom_id") */
    public $phonenumber;

    /** @ODM\ReferenceOne(targetDocument=CmsUser::class, cascade={"merge"}) */
    public $user;

    public function setUser(CmsUser $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
