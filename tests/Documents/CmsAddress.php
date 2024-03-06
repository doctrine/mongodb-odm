<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;

#[Index(keys: ['country' => 'asc', 'zip' => 'asc', 'city' => 'asc'])]
#[ODM\Document]
class CmsAddress
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $country;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $zip;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $city;

    /** @var CmsUser|null */
    #[ODM\ReferenceOne(targetDocument: CmsUser::class)]
    public $user;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?CmsUser
    {
        return $this->user;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getZipCode(): ?string
    {
        return $this->zip;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setUser(CmsUser $user): void
    {
        if ($this->user === $user) {
            return;
        }

        $this->user = $user;
        $user->setAddress($this);
    }
}
