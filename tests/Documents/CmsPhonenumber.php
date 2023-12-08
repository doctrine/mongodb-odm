<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class CmsPhonenumber
{
    /** @var int|string|null */
    #[ODM\Id(strategy: 'NONE', type: 'custom_id')]
    public $phonenumber;

    /** @var CmsUser|null */
    #[ODM\ReferenceOne(targetDocument: CmsUser::class, cascade: ['merge'])]
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
