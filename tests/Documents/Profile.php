<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Profile
{
    /** @ODM\Id */
    private $profileId;

    /** @ODM\Field */
    private $firstName;

    /** @ODM\Field */
    private $lastName;

    /** @ODM\ReferenceOne(targetDocument=File::class, cascade={"all"}) */
    private $image;

    public function setProfileId($profileId): void
    {
        $this->profileId = $profileId;
    }

    public function getProfileId()
    {
        return $this->profileId;
    }

    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setImage(File $image): void
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }
}
